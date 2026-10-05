<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image', 'folder', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    /** Absolute public URL for the category image (or null). */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        // New rows store only the file name; legacy rows hold a full path.
        if (!str_contains($this->image, '/')) {
            return Upload::url(Upload::categoryImageDir($this->folder), $this->image);
        }
        return str_starts_with($this->image, 'upload/')
            ? asset($this->image)
            : asset(Storage::url($this->image));
    }
}
