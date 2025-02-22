<?php

namespace App\Services;

use App\Models\Chapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoGenerationService
{
    protected Chapter $chapter;
    protected array $config;

    public function __construct(Chapter $chapter)
    {
        $this->chapter = $chapter;
        $this->config = [
            'output_path' => 'videos',
            'temp_path' => 'temp',
            'audio_format' => 'mp3',
            'video_format' => 'mp4',
            'frame_rate' => 24,
        ];
    }

    public function generate(): bool
    {
        try {
            $this->chapter->update(['status' => 'processing']);

            // 1. Prepare images from chapter content
            $images = $this->prepareImages();
            
            // 2. Generate narration text
            $narrationText = $this->generateNarrationText();
            
            // 3. Convert text to speech
            $audioPath = $this->generateAudio($narrationText);
            
            // 4. Combine images and audio into video
            $videoPath = $this->createVideo($images, $audioPath);
            
            // 5. Update chapter with video information
            $this->chapter->update([
                'status' => 'processed',
                'metadata' => array_merge($this->chapter->metadata ?? [], [
                    'video_path' => $videoPath,
                    'generated_at' => now()->toDateTimeString(),
                ])
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Video generation failed for chapter {$this->chapter->id}: {$e->getMessage()}");
            $this->chapter->update([
                'status' => 'failed',
                'metadata' => array_merge($this->chapter->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toDateTimeString(),
                ])
            ]);
            return false;
        }
    }

    protected function prepareImages(): array
    {
        $images = [];
        foreach ($this->chapter->content['images'] ?? [] as $imageUrl) {
            $localPath = $this->downloadImage($imageUrl);
            if ($localPath) {
                $images[] = $localPath;
            }
        }
        return $images;
    }

    protected function downloadImage(string $url): ?string
    {
        try {
            $contents = file_get_contents($url);
            $filename = basename($url);
            $path = $this->config['temp_path'] . '/' . $filename;
            
            Storage::put($path, $contents);
            return Storage::path($path);
        } catch (\Exception $e) {
            Log::error("Failed to download image {$url}: {$e->getMessage()}");
            return null;
        }
    }

    protected function generateNarrationText(): string
    {
        try {
            $client = new \OpenAI\Client(config('services.openai.api_key'));
            
            $prompt = "Generate a narration script for a manga chapter with the following details:\n";
            $prompt .= "Title: {$this->chapter->title}\n";
            $prompt .= "Chapter Number: {$this->chapter->chapter_number}\n";
            
            if (isset($this->chapter->content['text'])) {
                $prompt .= "Content: {$this->chapter->content['text']}\n";
            }
            
            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional manga narrator. Create an engaging narration script that captures the essence of the manga chapter, focusing on key story elements and emotional moments. Keep the narration concise yet impactful.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000
            ]);
            
            if (!isset($response->choices[0]->message->content)) {
                throw new \RuntimeException('Failed to generate narration text: Invalid API response');
            }
            
            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error("Failed to generate narration for chapter {$this->chapter->id}: {$e->getMessage()}");
            throw new \RuntimeException("Failed to generate narration: {$e->getMessage()}");
        }
    }

    protected function generateAudio(string $text): string
    {
        try {
            $polly = new \Aws\Polly\PollyClient([
                'version' => 'latest',
                'region'  => config('services.aws.region'),
                'credentials' => [
                    'key'    => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ]
            ]);
            
            // Split text into chunks if it's too long (Polly has a 3000 character limit)
            $chunks = str_split($text, 2900);
            $audioStreams = [];
            
            foreach ($chunks as $chunk) {
                $result = $polly->synthesizeSpeech([
                    'Text' => $chunk,
                    'OutputFormat' => $this->config['audio_format'],
                    'VoiceId' => 'Matthew', // Neural voice for better quality
                    'Engine' => 'neural',
                    'TextType' => 'text', // Can be changed to 'ssml' for more control
                    'SampleRate' => '24000' // High quality audio
                ]);
                
                if (!isset($result['AudioStream'])) {
                    throw new \RuntimeException('Failed to generate audio: No audio stream in response');
                }
                
                $audioStreams[] = $result['AudioStream']->getContents();
            }
            
            // Generate a unique filename for the audio
            $audioPath = $this->config['temp_path'] . '/' . uniqid('audio_') . '.' . $this->config['audio_format'];
            
            // Combine all audio chunks
            $combinedAudio = implode('', $audioStreams);
            Storage::put($audioPath, $combinedAudio);
            
            $fullPath = Storage::path($audioPath);
            
            // Verify the audio file was created successfully
            if (!file_exists($fullPath)) {
                throw new \RuntimeException('Failed to save audio file');
            }
            
            return $fullPath;
        } catch (\Exception $e) {
            Log::error("Failed to generate audio for chapter {$this->chapter->id}: {$e->getMessage()}");
            throw new \RuntimeException("Failed to generate audio: {$e->getMessage()}");
        }
    }

    protected function createVideo(array $images, string $audioPath): string
    {
        try {
            $videoPath = $this->config['output_path'] . '/' . uniqid('video_') . '.' . $this->config['video_format'];
            $tempPath = Storage::path($this->config['temp_path']);
            $outputPath = Storage::path($videoPath);
            
            // Create a temporary file containing the list of images with duration
            $listFile = $tempPath . '/images.txt';
            
            // Get audio duration using FFProbe
            $ffprobe = \FFMpeg\FFProbe::create();
            $audioInfo = $ffprobe->format($audioPath);
            $duration = floatval($audioInfo->get('duration'));
            
            // Calculate time per image based on audio duration and add buffer for transitions
            $transitionDuration = 1.0; // Increased for smoother transitions
            $effectiveDuration = $duration - ($transitionDuration * (count($images) + 1));
            $timePerImage = max($effectiveDuration / count($images), 3.0); // Minimum 3 seconds per image
            
            // Create the input file with proper duration for each image
            $fileContent = '';
            foreach ($images as $index => $image) {
                if (!file_exists($image)) {
                    throw new \RuntimeException("Image file not found: {$image}");
                }
                $fileContent .= "file '{$image}'"
                    . "\nduration {$timePerImage}\n";
            }
            file_put_contents($listFile, $fileContent);
            
            // Create FFMpeg instance with optimized configuration
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries' => 'ffmpeg',
                'ffprobe.binaries' => 'ffprobe',
                'timeout' => 7200,
                'ffmpeg.threads' => 0,
                'temporary_directory' => $tempPath
            ]);
            
            // Create video from images
            $video = $ffmpeg->open('concat:' . $listFile);
            
            // Configure video filters for enhanced quality output
            $video->filters()
                ->custom([
                    // Enhanced scaling with better quality and sharpness
                    'scale=1920:1080:force_original_aspect_ratio=decrease:flags=lanczos+accurate_rnd',
                    // Center the image with padding and gaussian blur effect for background
                    'split[main][bg];[bg]scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080,boxblur=20:20[blurred];[blurred][main]overlay=(W-w)/2:(H-h)/2',
                    // Smoother frame interpolation with motion interpolation
                    "minterpolate=fps={$this->config['frame_rate']}:mi_mode=mci:mc_mode=aobmc:me_mode=bidir:vsbmc=1",
                    // Enhanced transitions with cross-fade and motion
                    'tblend=all_mode=overlay:all_opacity=0.8',
                    // Advanced Ken Burns effect with smooth acceleration
                    'zoompan=z=\'if(lte(on,1),1.3,max(1.001,1.3-0.3*on/n))\':' .
                        'd=' . ($timePerImage * $this->config['frame_rate']) . ':' .
                        's=1920x1080:' .
                        'x=\'iw/2-(iw/zoom/2)+sin(on/(8*PI))*100\':' .
                        'y=\'ih/2-(ih/zoom/2)+cos(on/(8*PI))*100\':' .
                        'fps=' . $this->config['frame_rate']
                ])
                ->synchronize();
            
            // Configure video format with optimized quality settings
            $format = new \FFMpeg\Format\Video\X264();
            $format
                ->setKiloBitrate(8000) // 8Mbps video bitrate for higher quality
                ->setAudioCodec('aac')
                ->setAudioKiloBitrate(320) // 320kbps audio bitrate for high quality audio
                ->setAudioChannels(2)
                ->setPasses(2) // Two-pass encoding for optimal quality
                ->setThreads(0) // Auto-detect number of threads
                ->addAdditionalParameter('-preset', 'slow') // Better compression
                ->addAdditionalParameter('-profile:v', 'high') // High profile for better quality
                ->addAdditionalParameter('-movflags', '+faststart'); // Enable streaming
            
            // Add audio to the video with proper resampling
            $video->addFilter(new \FFMpeg\Filters\Audio\AudioResamplableFilter());
            
            // Save the video with the audio
            $video->save($format, $outputPath);
            
            // Clean up temporary files with error handling
            $this->cleanupTemporaryFiles([$listFile, $audioPath, ...$images]);
            
            return $videoPath;
        } catch (\Exception $e) {
            // Attempt to clean up any temporary files even if video creation fails
            $this->cleanupTemporaryFiles([$listFile ?? null, $audioPath, ...$images]);
            Log::error("Failed to create video for chapter {$this->chapter->id}: {$e->getMessage()}");
            throw new \RuntimeException("Failed to create video: {$e->getMessage()}");
        }
    }

    /**
     * Clean up temporary files safely
     *
     * @param array $files Array of file paths to clean up
     */
    protected function cleanupTemporaryFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file && file_exists($file)) {
                try {
                    unlink($file);
                } catch (\Exception $e) {
                    Log::warning("Failed to clean up temporary file {$file}: {$e->getMessage()}");
                }
            }
        }
    }
}