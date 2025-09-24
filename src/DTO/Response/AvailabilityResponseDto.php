<?php

namespace App\DTO\Response;

class AvailabilityResponseDto
{
    /** @var array<array{id: int, firstName: string, lastName: string, availableSlots: array<string>}> */
    public array $stylists;

    /**
     * @param array $stylistsWithSlots Array where key is stylist ID and value is array of available time slots
     * @return self
     */
    public static function fromStylistsWithSlots(array $stylistsWithSlots): self
    {
        $dto = new self();
        $dto->stylists = [];

        foreach ($stylistsWithSlots as $stylistId => $data) {
            $dto->stylists[] = [
                'id' => $stylistId,
                'firstName' => $data['stylist']->getUser()->getFirstName(),
                'lastName' => $data['stylist']->getUser()->getLastName(),
                'availableSlots' => $data['slots'],
            ];
        }

        return $dto;
    }
}
