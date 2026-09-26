<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'type',
        'content',
    ];

    /**
     * Gantikan placeholder variabel seperti {nama}, {total}, {url_nota}
     */
    public function render(array $variables = []): string
    {
        $text = $this->content;
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }
}
