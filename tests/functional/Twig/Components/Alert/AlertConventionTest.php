<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units\Twig\Components\Alert;

use Glpi\Tests\GLPITestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Fails on any alert markup that is built by hand instead of calling <twig:Alert>.
 *
 * The allowlist is what is left to convert. It only ever shrinks. Adding an entry
 * means adding duplicated markup on purpose, and needs a justification in review.
 */
class AlertConventionTest extends GLPITestCase
{
    /**
     * Directories scanned, relative to GLPI_ROOT, and the file extension read in each.
     */
    private const SCANNED = [
        'templates' => 'twig',
        'src'       => 'php',
        'front'     => 'php',
        'ajax'      => 'php',
    ];

    /**
     * The component's own templates are the one place the markup is legitimate.
     */
    private const SKIPPED_PATH = 'templates/twig_components/Alert/';

    /**
     * Alert markup that is deliberately not converted, as path => number of blocks.
     *
     * Every entry needs a reason. Adding one means writing duplicated markup on
     * purpose, and has to be justified in review. This list only ever shrinks.
     *
     * @var array<string, int>
     */
    private const PERMITTED_HAND_ROLLED_ALERTS = [
        // This one sits in a <<<HTML heredoc that is echoed straight to the browser,
        // not a <<<TWIG heredoc rendered through TemplateRenderer::renderFromStringTemplate().
        // A component tag here would never be processed by Twig; converting it means
        // routing the heredoc through Twig first. It was converted once during the
        // campaign and then reverted, when the result reached the browser as literal
        // markup instead of rendered HTML.
        'src/CommonITILValidation.php'                                                                            => 1,

        // The alert is nested inside a larger echoed block in each of these; extracting
        // it means rewriting the surrounding PHP rendering code.
        'src/DisplayPreference.php'                                                                              => 1,
        'src/Dropdown.php'                                                                                       => 1,
        'src/Glpi/Error/ErrorDisplayHandler/HtmlErrorDisplayHandler.php'                                         => 1,
        'src/Glpi/Marketplace/View.php'                                                                          => 2,
        'src/Group_User.php'                                                                                     => 1,
        'src/NetworkPortInstantiation.php'                                                                       => 1,
        'src/ProjectCost.php'                                                                                    => 1,
        'src/Reservation.php'                                                                                    => 1,
        'src/Rule.php'                                                                                           => 1,
        'src/Stat.php'                                                                                           => 1,

        // The alert classes are carried by an <h3>, and the component always renders
        // a <div>, so converting would remove a heading from the document outline.
        // Arbitrated with the heading-levels work (2026-08-25-a11y-heading-levels-406-*).
        'templates/pages/2fa/macros.html.twig'                                                                  => 1,
    ];

    public function testNoHandRolledAlertMarkupOutsideTheAllowlist(): void
    {
        $found = $this->countHandRolledAlerts();

        $permitted = self::PERMITTED_HAND_ROLLED_ALERTS;
        ksort($permitted);

        $this->assertSame(
            $permitted,
            $found,
            "Hand-built alert markup does not match the allowlist.\n"
            . "Use <twig:Alert>, <twig:Alert:Info>, <twig:Alert:Warning>, <twig:Alert:Danger>\n"
            . "or <twig:Alert:Success> instead of writing class=\"alert ...\" by hand.\n"
            . "If a block is deliberately not converted, add it to "
            . "self::PERMITTED_HAND_ROLLED_ALERTS with a reason.\n"
            . "This comparison is order-independent: both sides are sorted by path."
        );
    }

    /**
     * @return array<string, int> path => number of hand-built alert blocks, sorted by path
     */
    private function countHandRolledAlerts(): array
    {
        $found = [];

        foreach (self::SCANNED as $dir => $extension) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(GLPI_ROOT . '/' . $dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== $extension) {
                    continue;
                }

                $path = str_replace(GLPI_ROOT . '/', '', $file->getPathname());

                if (str_starts_with($path, self::SKIPPED_PATH)) {
                    continue;
                }

                $count = $this->countInSource((string) file_get_contents($file->getPathname()));

                if ($count > 0) {
                    $found[$path] = $count;
                }
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * Counts class attributes carrying "alert" as a class token.
     *
     * The `s` modifier lets the attribute value span several lines, a style this
     * codebase already uses (see templates/pages/admin/form/form_editor.html.twig).
     * Without it a multi-line class attribute would be invisible here, and the CI
     * would report green while the markup came back.
     */
    private function countInSource(string $source): int
    {
        if (preg_match_all('/class\s*=\s*(["\'])(.*?)\1/s', $source, $matches) === 0) {
            return 0;
        }

        $count = 0;

        foreach ($matches[2] as $class_attribute) {
            if (in_array('alert', preg_split('/\s+/', trim($class_attribute)), true)) {
                $count++;
            }
        }

        return $count;
    }
}
