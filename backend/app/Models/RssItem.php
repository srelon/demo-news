<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RssItem extends Model
{
    // status: imported | rejected | failed | ignored
    // 'ignored' — deleted from the admin list, kept for dedup
    protected $fillable = [
        'rss_source_id', 'guid_hash', 'guid', 'link', 'title',
        'status', 'reason', 'reason_code', 'article_id',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(RssSource::class, 'rss_source_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
