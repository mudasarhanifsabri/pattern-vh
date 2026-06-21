<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['support_category_id', 'title', 'body', 'roles', 'is_active'])]
class QuickReply extends Model
{
    protected function casts(): array { return ['roles' => 'array', 'is_active' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(SupportCategory::class, 'support_category_id'); }
}
