<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
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

/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 */

namespace App\Twig;

use App\Entity\Base\AbstractDBElement;
use App\Entity\Parts\MeasurementUnit;
use App\Entity\PriceInformations\Currency;
use App\Services\AmountFormatter;
use App\Services\Attachments\AttachmentURLGenerator;
use App\Services\EntityURLGenerator;
use App\Services\FAIconGenerator;
use App\Services\MarkdownParser;
use App\Services\MoneyFormatter;
use App\Services\SIFormatter;
use App\Services\Trees\TreeViewGenerator;
use Brick\Math\BigDecimal;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    protected $entityURLGenerator;
    protected $markdownParser;
    protected $serializer;
    protected $treeBuilder;
    protected $moneyFormatter;
    protected $siformatter;
    protected $amountFormatter;
    protected $attachmentURLGenerator;
    protected $FAIconGenerator;
    protected $translator;

    public function __construct(EntityURLGenerator $entityURLGenerator, MarkdownParser $markdownParser,
        SerializerInterface $serializer, TreeViewGenerator $treeBuilder,
        MoneyFormatter $moneyFormatter,
        SIFormatter $SIFormatter, AmountFormatter $amountFormatter,
        AttachmentURLGenerator $attachmentURLGenerator,
        FAIconGenerator $FAIconGenerator, TranslatorInterface $translator)
    {
        $this->entityURLGenerator = $entityURLGenerator;
        $this->markdownParser = $markdownParser;
        $this->serializer = $serializer;
        $this->treeBuilder = $treeBuilder;
        $this->moneyFormatter = $moneyFormatter;
        $this->siformatter = $SIFormatter;
        $this->amountFormatter = $amountFormatter;
        $this->attachmentURLGenerator = $attachmentURLGenerator;
        $this->FAIconGenerator = $FAIconGenerator;
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('entityURL', [$this, 'generateEntityURL']),
            new TwigFilter('markdown', [$this->markdownParser, 'markForRendering'], [
                'pre_escape' => 'html',
                'is_safe' => ['html'],
            ]),
            new TwigFilter('moneyFormat', [$this, 'formatCurrency']),
            new TwigFilter('siFormat', [$this, 'siFormat']),
            new TwigFilter('amountFormat', [$this, 'amountFormat']),
            new TwigFilter('loginPath', [$this, 'loginPath']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('instanceof', function ($var, $instance) {
                return $var instanceof $instance;
            }),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('generateTreeData', [$this, 'treeData']),
            new TwigFunction('attachment_thumbnail', [$this->attachmentURLGenerator, 'getThumbnailURL']),
            new TwigFunction('ext_to_fa_icon', [$this->FAIconGenerator, 'fileExtensionToFAType']),
        ];
    }

    public function treeData(AbstractDBElement $element, string $type = 'newEdit'): string
    {
        $tree = $this->treeBuilder->getTreeView(\get_class($element), null, $type, $element);

        return json_encode($tree);
    }

    /**
     * This function/filter generates an path.
     *
     * @return string
     */
    public function loginPath(string $path): string
    {
        $parts = explode('/', $path);
        //Remove the part with
        unset($parts[1]);

        return implode('/', $parts);
    }

    public function generateEntityURL(AbstractDBElement $entity, string $method = 'info'): string
    {
        return $this->entityURLGenerator->getURL($entity, $method);
    }

    public function formatCurrency($amount, ?Currency $currency = null, int $decimals = 5)
    {
        if ($amount instanceof BigDecimal) {
            $amount = (string) $amount;
        }

        return $this->moneyFormatter->format($amount, $currency, $decimals);
    }

    public function siFormat($value, $unit, $decimals = 2, bool $show_all_digits = false)
    {
        return $this->siformatter->format($value, $unit, $decimals, $show_all_digits);
    }

    public function amountFormat($value, ?MeasurementUnit $unit, array $options = [])
    {
        return $this->amountFormatter->format($value, $unit, $options);
    }
}
