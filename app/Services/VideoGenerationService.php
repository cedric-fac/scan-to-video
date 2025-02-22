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
        $videoPath = $this->config['output_path'] . '/' . uniqid('video_') . '.' . $this->config['video_format'];
        $tempPath = Storage::path($this->config['temp_path']);
        $outputPath = Storage::path($videoPath);
        
        // Create a temporary file containing the list of images
        $listFile = $tempPath . '/images.txt';
        file_put_contents($listFile, implode(PHP_EOL, array_map(function($image) {
            return "file '$image'";
        }, $images)));
        
        // Create video from images
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open('concat:' . $listFile);
        
        // Set video duration based on audio length        $audioInfo = $ffmpeg->getFFProbe()->format($audioPath);
        $duration = $audioInfo->get('duration');
        $frameCount = ceil($duration * $this->config['frame_rate']);
        
        // Add audio to video
        $video
            ->filters()
            ->framerate($this->config['frame_rate'])
            ->custom("scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2")
            ->synchronize();
        
        $format = new X264();
        $format->setAudioCodec("aac");
        
        $video->save($format, $outputPath);
        
        // Clean up temporary files
        @unlink($listFile);
        @unlink($audioPath);
        foreach ($images as $image) {
            @unlink($image);
        }
        
        return $videoPath;
    }
}