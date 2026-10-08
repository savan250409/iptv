<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'title', 'episode_number', 'video_file', 'thumbnail', 'sort_order'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Folder of this video's category (for rebuilding paths). */
    private function categoryFolder(): ?string
    {
        return optional($this->category)->folder;
    }

    /** Absolute public URL for the uploaded video file. */
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video_file) {
            return null;
        }
        if (!str_contains($this->video_file, '/')) {
            return Upload::url(Upload::videoDir($this->categoryFolder()), $this->video_file);
        }
        return str_starts_with($this->video_file, 'upload/')
            ? asset($this->video_file)
            : asset(Storage::url($this->video_file));
    }

    /** Absolute public URL for the auto-generated thumbnail (or null). */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }
        if (!str_contains($this->thumbnail, '/')) {
            return Upload::url(Upload::thumbDir($this->categoryFolder()), $this->thumbnail);
        }
        return str_starts_with($this->thumbnail, 'upload/')
            ? asset($this->thumbnail)
            : asset(Storage::url($this->thumbnail));
    }
}
