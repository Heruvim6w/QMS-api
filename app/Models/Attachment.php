<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Attachment",
 *     type="object",
 *     title="Attachment",
 *     description="Вложение к сообщению (файл, изображение и т. п.)",
 *     required={"message_id", "file_path"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", description="Уникальный идентификатор вложения", example=123),
 *     @OA\Property(property="message_id", type="integer", format="int64", description="ID сообщения, к которому прикреплено вложение", example=456),
 *     @OA\Property(property="file_path", type="string", description="Путь к файлу на сервере", example="/storage/uploads/photo.jpg"),
 *     @OA\Property(property="mime_type", type="string", description="MIME-тип файла (например, image/jpeg)", example="image/jpeg", nullable=true),
 *     @OA\Property(property="size", type="integer", description="Размер файла в байтах", example=102400, nullable=true),
 *     @OA\Property(property="name", type="string", description="Оригинальное имя файла (как было загружено)", example="отчёт_2025.pdf", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Дата создания записи", example="2025-01-15T10:30:00Z", nullable=true)
 * )
 */
class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'file_path',
        'mime_type',
        'size',
        'name',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
