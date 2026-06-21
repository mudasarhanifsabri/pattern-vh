<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'color', 'is_active', 'sort_order'])]
class SupportCategory extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function quickReplies(): HasMany { return $this->hasMany(QuickReply::class); }
    public function autoReplyRules(): HasMany { return $this->hasMany(AutoReplyRule::class); }
}
