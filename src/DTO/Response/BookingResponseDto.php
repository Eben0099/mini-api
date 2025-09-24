<?php

namespace App\DTO\Response;

use App\Entity\Booking;

class BookingResponseDto
{
    public int $id;
    public string $status;
    public string $startAt;
    public string $endAt;
    public string $createdAt;
    public string $updatedAt;

    public array $salon;
    public array $stylist;
    public array $service;
    public array $client;

    public static function fromEntity(Booking $booking): self
    {
        $dto = new self();
        $dto->id = $booking->getId();
        $dto->status = $booking->getStatus();
        $dto->startAt = $booking->getStartAt()->format('Y-m-d H:i:s');
        $dto->endAt = $booking->getEndAt()->format('Y-m-d H:i:s');
        $dto->createdAt = $booking->getCreatedAt()->format('Y-m-d H:i:s');
        $dto->updatedAt = $booking->getUpdatedAt()->format('Y-m-d H:i:s');

        $dto->salon = [
            'id' => $booking->getSalon()->getId(),
            'name' => $booking->getSalon()->getName(),
            'city' => $booking->getSalon()->getCity(),
        ];

        $dto->stylist = [
            'id' => $booking->getStylist()->getId(),
            'firstName' => $booking->getStylist()->getUser()->getFirstName(),
            'lastName' => $booking->getStylist()->getUser()->getLastName(),
        ];

        $dto->service = [
            'id' => $booking->getService()->getId(),
            'name' => $booking->getService()->getName(),
            'durationMinutes' => $booking->getService()->getDurationMinutes(),
        ];

        $dto->client = [
            'id' => $booking->getClient()->getId(),
            'firstName' => $booking->getClient()->getFirstName(),
            'lastName' => $booking->getClient()->getLastName(),
        ];

        return $dto;
    }
}
