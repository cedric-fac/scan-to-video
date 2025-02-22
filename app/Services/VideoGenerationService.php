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
            'audio_format' => 'aac',
            'audio_bitrate' => '320k',
            'audio_sample_rate' => '48000',
            'audio_channels' => 2,
            'video_format' => 'mp4',
            'frame_rate' => 60,
            'blur_intensity' => 80, // Increased for more pronounced blur effect
            'blur_steps' => 12, // Increased for smoother blur gradient
            'background_darkness' => 0.35, // Adjusted for better contrast
            'background_saturation' => 0.6, // Increased for more vibrant backgrounds
            'background_blur_sigma' => 8.0, // Increased for stronger gaussian blur
            'motion_blur_strength' => 0.8,
            'transition_duration' => 2.5,
            'min_image_duration' => 4.0,
            'ken_burns_zoom_range' => [1.1, 1.5],
            'ken_burns_pan_range' => 180,
            'video_bitrate' => '15M',
            'video_preset' => 'veryslow',
            'video_profile' => 'high',
            'video_tune' => 'film',
            'interpolation_mode' => 'mci',
            'motion_estimation' => 'hexbs',
            'denoiser_strength' => '2:2:8:8',
            'sharpness_filter' => true,
            'advanced_interpolation' => true,
            'frame_blending' => 0.8,
            'motion_compensation' => 'obmc',
            'scene_detection' => true,
            'scene_threshold' => 0.25,
            'temporal_smoothing' => true,
            'smoothing_frames' => 3
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
                    'scale=1920:1080:force_original_aspect_ratio=decrease:flags=lanczos+accurate_rnd+full_chroma_int+bicubic',
                    // Center the image with enhanced gaussian blur and dynamic background processing
                    'split[main][bg];[bg]scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080,' .
                    "boxblur={$this->config['blur_intensity']}:{$this->config['blur_steps']}:lr=24:cr=24,eq=brightness=-0.2:saturation={$this->config['background_saturation']}:contrast=2.6," .
                    "gblur=sigma={$this->config['background_blur_sigma']}:steps=32:planes=1:sigmaV=3.0,colorbalance=rs=-0.4:gs=-0.4:bs=-0.4:rm=0.3:gm=0.3:bm=0.3:rh=0.4:gh=0.4:bh=0.4," .
                    "colorlevels=rimin=0.18:gimin=0.18:bimin=0.18:rimax={$this->config['background_darkness']}:gimax={$this->config['background_darkness']}:bimax={$this->config['background_darkness']}:amax=1.0:gamma=1.4[blurred];" .
                    '[blurred][main]overlay=(W-w)/2:(H-h)/2:format=auto:eval=frame,format=yuv420p',
                    // Advanced frame interpolation with motion estimation and frame blending
                    "minterpolate=fps={$this->config['frame_rate']}:mi_mode={$this->config['interpolation_mode']}:mc_mode={$this->config['motion_compensation']}:me_mode={$this->config['motion_estimation']}:vsbmc=1:mb_size=16:search_param=32:me_thresh=50:vsbmc_thresh=4:scd_thresh=10:blend={$this->config['frame_blending']}:scd={$this->config['scene_detection'] ? 'fdiff' : 'none'}:scd_threshold={$this->config['scene_threshold']}:mci_strength=0.8:mci_radius={$this->config['smoothing_frames']}:mci_mode=adaptive",
                    // Enhanced transitions with advanced motion-compensated interpolation and cross-fade
                    "tblend=all_mode=overlay,all_opacity=1:framestep=1," .
                    "tblend=all_mode=multiply:all_opacity={$this->config['motion_blur_strength']}," .
                    "minterpolate=mi_mode=mci:mc_mode=aobmc:me_mode=bilat:vsbmc=1:fps={$this->config['frame_rate']}:me=epzs:mb_size=16:vsbmc=1:search=dia," .
                    "hqdn3d={$this->config['denoiser_strength']}," .
                    // Enhanced fade transitions with cross-fade and motion blur
                    "fade=t=in:st=0:d={$this->config['transition_duration']}:alpha=1," .
                    "fade=t=out:st=" . ($timePerImage - $this->config['transition_duration']) . ":d={$this->config['transition_duration']}:alpha=1," .
                    "tblend=all_mode=darken:all_opacity=0.6:framestep=1",
                    // Advanced Ken Burns effect with dynamic zoom and pan parameters
                    'zoompan=z=\'min(max(zoom,1.001),' . $this->config['ken_burns_zoom_range'][1] . ')\':\'' .
                        'd=' . ($timePerImage * $this->config['frame_rate']) . ':\'' .
                        's=1920x1080:\'' .
                        'x=\'iw/2-(iw/zoom/2)+sin(on/(8*PI))*' . $this->config['ken_burns_pan_range'] . '*sin(on/n*2*PI):\'' .
                        'y=\'ih/2-(ih/zoom/2)+cos(on/(10*PI))*' . $this->config['ken_burns_pan_range'] . '*cos(on/n*2*PI):\'' .
                        'zoom=\'if(lte(on,1),' . $this->config['ken_burns_zoom_range'][1] . ',if(gte(on,n-60),' . $this->config['ken_burns_zoom_range'][0] . ',' .
                            $this->config['ken_burns_zoom_range'][1] . '-' . ($this->config['ken_burns_zoom_range'][1] - $this->config['ken_burns_zoom_range'][0]) . '*(0.5-0.5*cos((on/n)*PI))))\':' .
                        'fps=' . $this->config['frame_rate'] .
                    ($this->config['sharpness_filter'] ? ',unsharp=5:5:1.0:5:5:0.0' : '')
                ])
                ->synchronize();
            
            // Configure video format with optimized quality settings
            $format = new \FFMpeg\Format\Video\X264();
            $format
                ->setKiloBitrate(intval($this->config['video_bitrate']))
                ->setAudioCodec('aac')
                ->setAudioKiloBitrate(320)
                ->setAudioChannels(2)
                ->setPasses(2)
                ->setThreads(0)
                ->addAdditionalParameter('-preset', $this->config['video_preset'])
                ->addAdditionalParameter('-profile:v', $this->config['video_profile'])
                ->addAdditionalParameter('-tune', $this->config['video_tune'])
                ->addAdditionalParameter('-movflags', '+faststart');
            
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