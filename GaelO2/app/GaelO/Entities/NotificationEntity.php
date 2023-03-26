<?php

namespace App\GaelO\Entities;

class NotificationEntity {
    public int $id;
    public string $object;
    public string $message;

    public static function fillFromDBReponseArray(array $array): NotificationEntity
    {
        $documentationEntity  = new NotificationEntity();
        $data = json_decode($array['data']);
        $documentationEntity->id = $array['id'];
        $documentationEntity->object = $data['object'];
        $documentationEntity->message = $data['message'];

        return $documentationEntity;
    }
}