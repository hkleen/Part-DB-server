<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2022 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Entity\LogSystem;

use App\Entity\Base\AbstractDBElement;
use App\Entity\Contracts\LogWithCommentInterface;
use App\Entity\Contracts\LogWithEventUndoInterface;
use App\Entity\Contracts\TimeTravelInterface;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * @ORM\Entity()
 */
class ElementEditedLogEntry extends AbstractLogEntry implements TimeTravelInterface, LogWithCommentInterface, LogWithEventUndoInterface
{
    protected string $typeString = 'element_edited';

    public function __construct(AbstractDBElement $changed_element)
    {
        parent::__construct();
        $this->level = self::LEVEL_INFO;

        $this->setTargetElement($changed_element);
    }

    /**
     * Checks if this log contains infos about which fields has changed.
     */
    public function hasChangedFieldsInfo(): bool
    {
        return isset($this->extra['f']) || $this->hasOldDataInformations();
    }

    /**
     * Return the names of all fields that were changed during the change.
     *
     * @return string[]
     */
    public function getChangedFields(): array
    {
        if ($this->hasOldDataInformations()) {
            return array_keys($this->getOldData());
        }

        return $this->extra['f'] ?? [];
    }

    /**
     * Set the fields that were changed during this element change.
     *
     * @param string[] $changed_fields The names of the fields that were changed during the elements
     *
     * @return $this
     */
    public function setChangedFields(array $changed_fields): self
    {
        $this->extra['f'] = $changed_fields;

        return $this;
    }

    /**
     * Sets the old data for this entry.
     *
     * @return $this
     */
    public function setOldData(array $old_data): self
    {
        $this->extra['d'] = $old_data;

        return $this;
    }

    public function hasOldDataInformations(): bool
    {
        return !empty($this->extra['d']);
    }

    public function getOldData(): array
    {
        return $this->extra['d'] ?? [];
    }

    public function hasComment(): bool
    {
        return isset($this->extra['m']);
    }

    public function getComment(): ?string
    {
        return $this->extra['m'] ?? null;
    }

    public function setComment(?string $new_comment): LogWithCommentInterface
    {
        $this->extra['m'] = $new_comment;

        return $this;
    }

    public function isUndoEvent(): bool
    {
        return isset($this->extra['u']);
    }

    public function getUndoEventID(): ?int
    {
        return $this->extra['u'] ?? null;
    }

    public function setUndoneEvent(AbstractLogEntry $event, string $mode = 'undo'): LogWithEventUndoInterface
    {
        $this->extra['u'] = $event->getID();

        if ('undo' === $mode) {
            $this->extra['um'] = 1;
        } elseif ('revert' === $mode) {
            $this->extra['um'] = 2;
        } else {
            throw new InvalidArgumentException('Passed invalid $mode!');
        }

        return $this;
    }

    public function getUndoMode(): string
    {
        $mode_int = $this->extra['um'] ?? 1;
        if (1 === $mode_int) {
            return 'undo';
        }

        return 'revert';
    }
}
