<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'ix_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::find($key)?->value ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function methodologyVersion(): string
    {
        return (string) static::get('methodology_version', '1.0');
    }
}
