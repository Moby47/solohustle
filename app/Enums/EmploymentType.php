<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** The kind of contract. */
enum EmploymentType: string implements HasLabel
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Freelance = 'freelance';
    case Internship = 'internship';

    public function getLabel(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
            self::Contract => 'Contract',
            self::Freelance => 'Freelance',
            self::Internship => 'Internship',
        };
    }

    /**
     * The value Google's JobPosting data expects.
     */
    public function schemaValue(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Contract, self::Freelance => 'CONTRACTOR',
            self::Internship => 'INTERN',
        };
    }
}
