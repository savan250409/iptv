<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'image', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    /** Absolute public URL for the sub-category image (rebuilt from the parent category folder). */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        if (!str_contains($this->image, '/')) {
            return Upload::url(Upload::subCategoryImageDir(optional($this->category)->folder), $this->image);
        }
        return str_starts_with($this->image, 'upload/')
            ? asset($this->image)
            : asset(Storage::url($this->image));
    }
}
