<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateVersion extends Model
{
    protected $fillable = ['message_template_id', 'version', 'language', 'body', 'components', 'variable_count', 'is_active'];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'is_active' => 'boolean',
            'variable_count' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function expectedVariableCount(): int
    {
        if ($this->variable_count > 0) {
            return $this->variable_count;
        }

        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $this->body, $matches);

        return count(array_unique($matches[1] ?? []));
    }
}
