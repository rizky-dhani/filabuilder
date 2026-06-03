<?php

namespace Filabuilder\Models;

use Filabuilder\Enums\PageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RalphJSmit\Laravel\SEO\Support\HasSEO;

class FilaBuilderPage extends Model
{
    use HasSEO;

    protected $table = 'pages';

    protected $fillable = [
        'title', 'slug', 'status', 'published_at',
        'content', 'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
        'status' => PageStatus::class,
    ];

    public function getHtmlAttribute(): ?string
    {
        return $this->content['html'] ?? null;
    }

    public function getCssAttribute(): ?string
    {
        return $this->content['css'] ?? null;
    }

    public function getProjectDataAttribute(): ?array
    {
        return $this->content['project_data'] ?? null;
    }

    public function scopePublished($query)
    {
        $query->where(function ($q) {
            $q->where('status', PageStatus::Published)
              ->orWhere(fn ($q) => $q
                  ->where('status', PageStatus::Scheduled)
                  ->where('published_at', '<=', now())
              );
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('filament.user_model', \Illuminate\Foundation\Auth\User::class),
            'created_by'
        );
    }
}
