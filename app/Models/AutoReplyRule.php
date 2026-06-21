<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['support_category_id', 'name', 'keywords', 'response', 'roles', 'priority', 'is_active'])]
class AutoReplyRule extends Model
{
    protected function casts(): array { return ['keywords' => 'array', 'roles' => 'array', 'is_active' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(SupportCategory::class, 'support_category_id'); }
}
