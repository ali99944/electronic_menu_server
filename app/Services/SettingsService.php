<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class SettingsService
{
    protected $settingsPath;
    protected $cache = []; // Simple cache for the request lifecycle

    public function __construct()
    {
        $this->settingsPath = storage_path('app/private/settings');
        if (!File::isDirectory($this->settingsPath)) {
            File::makeDirectory($this->settingsPath, 0755, true);
        }
    }

    /**
     * Load settings from a specific JSON file.
     *
     * @param string $fileKey e.g., 'general', 'contact'
     * @return array
     */
    protected function loadSettingsFile(string $fileKey): array
    {
        if (isset($this->cache[$fileKey])) {
            return $this->cache[$fileKey];
        }

        $filePath = $this->settingsPath . '/' . $fileKey . '.json';

        if (!File::exists($filePath)) {
            return []; // Return empty array if file doesn't exist
        }

        try {
            $content = File::get($filePath);
            $settings = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                 Log::error("Error decoding settings file [{$fileKey}.json]: " . json_last_error_msg());
                 return [];
            }
             $this->cache[$fileKey] = $settings ?: [];
             return $this->cache[$fileKey];
        } catch (\Exception $e) {
            Log::error("Error reading settings file [{$fileKey}.json]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save settings to a specific JSON file.
     *
     * @param string $fileKey e.g., 'general', 'contact'
     * @param array $settings
     * @return bool
     */
    protected function saveSettingsFile(string $fileKey, array $settings): bool
    {
        $filePath = $this->settingsPath . '/' . $fileKey . '.json';
        $this->cache[$fileKey] = $settings; // Update cache

        try {
            $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error encoding settings for file [{$fileKey}.json]: " . json_last_error_msg());
                return false;
            }
            return File::put($filePath, $json) !== false;
        } catch (\Exception $e) {
            Log::error("Error writing settings file [{$fileKey}.json]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all settings merged from different files.
     * Define the expected setting file keys here.
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        $allSettings = [];
        $settingFiles = ['contact']; // Define your setting groups

        foreach ($settingFiles as $fileKey) {
            $allSettings[$fileKey] = $this->loadSettingsFile($fileKey);
        }
        return $allSettings;
    }

    /**
     * Get a specific setting value using dot notation.
     *
     * @param string $key e.g., 'general.app_name', 'contact.email'
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key, 2);
        $fileKey = $keys[0];
        $settingKey = $keys[1] ?? null;

        $settings = $this->loadSettingsFile($fileKey);

        if ($settingKey === null) {
            return $settings ?: $default; // Return whole file content if no specific key
        }

        return Arr::get($settings, $settingKey, $default);
    }

    /**
     * Save multiple settings, distributing them to the correct files.
     * Expects an array like ['general.app_name' => 'New Name', 'contact.phone' => '123']
     * Or a nested array ['general' => ['app_name' => 'New Name'], 'contact' => ['phone' => '123']]
     *
     * @param array $settingsData
     * @return bool Returns true if all saves were successful.
     */
    public function saveSettings(array $settingsData): bool
    {
        $groupedSettings = [];

        // Group settings by file key (e.g., 'general', 'contact')
        foreach ($settingsData as $key => $value) {
            if (strpos($key, '.') !== false) {
                 // Handle dot notation keys from flat form submission
                $keys = explode('.', $key, 2);
                $fileKey = $keys[0];
                $settingKey = $keys[1];
                 // Use Arr::set for nested keys within the file group
                 Arr::set($groupedSettings[$fileKey], $settingKey, $value);
            } else {
                 // Handle pre-grouped keys if input is nested
                 if(is_array($value)) {
                     $groupedSettings[$key] = array_merge($this->loadSettingsFile($key), $value);
                 }
            }
        }


        $success = true;
        foreach ($groupedSettings as $fileKey => $settings) {
            // Merge with existing settings for that file to avoid overwriting unrelated keys
             $existingSettings = $this->loadSettingsFile($fileKey);
             $mergedSettings = array_merge($existingSettings, $settings); // New values overwrite old ones

             if (!$this->saveSettingsFile($fileKey, $mergedSettings)) {
                $success = false;
                Log::error("Failed to save settings file: {$fileKey}.json");
            }
        }

        // Clear cache after saving
        $this->cache = [];

        return $success;
    }
}
