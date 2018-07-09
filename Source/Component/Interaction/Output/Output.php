<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Output;

use Schilffarth\CommandLineInterface\Source\State;

class Output
{

    /**
     * Output is always displayed, even when --quiet is active
     */
    public const QUIET = 1;

    /**
     * Default output level
     */
    public const NORMAL = 2;

    /**
     * Verbose information, displayed when --verbose is active
     */
    public const DEBUG = 3;

    /**
     * Codes for colored console output, allows custom colors to be registered
     * @see Output::registerColor()
     */
    public $colors = [
        // Light grey, darker than usual white output
        'comment' => '0;37',
        // Info output is colored green
        'info' => '0;32',
        // Important debug output
        'debug' => '0;36',
        // Error output is colored red
        'error' => '0;31'
    ];

    /**
     * Output a message to the console
     * The command will highlight all registered colors with their mapped colors
     * @see Output::colors
     * @see Output::parseMessage()
     */
    public function write(string $message, int $verbosity = self::NORMAL, bool $nl = false): self
    {
        if ($this->verbosityDisallowsOutput($verbosity)) {
            // The message should not be outputted in the current verbosity level
            return $this;
        }

        // Replace colors, such as <info></info> with the mapped color attribute in $this->colors
        $message = $this->dissect($message);

        echo $message;

        if ($nl) {
            $this->nl();
        }

        return $this;
    }

    public function writeln(string $message, int $verbosity = self::NORMAL): self
    {
        $this->write($message, $verbosity, true);

        return $this;
    }

    /**
     * Wraps the message in <error></error> colors
     * Verbosity defaults to QUIET
     */
    public function error(string $message, $verbosity = self::QUIET): self
    {
        return $this->writeln("<error>$message</error>", $verbosity);
    }

    /**
     * Wraps the message in <info></info> colors
     */
    public function info(string $message, $verbosity = self::NORMAL): self
    {
        return $this->writeln("<info>$message</info>", $verbosity);
    }

    /**
     * Wraps the message in <comment></comment> colors
     */
    public function comment(string $message, $verbosity = self::NORMAL): self
    {
        return $this->writeln("<comment>$message</comment>", $verbosity);
    }

    /**
     * Wraps the message in <debug></debug>
     * Verbosity defaults to DEBUG
     */
    public function debug(string $message, $verbosity = self::DEBUG): self
    {
        return $this->writeln("<debug>$message</debug>", $verbosity);
    }

    /**
     * Outputs line breaks to the console
     */
    public function nl($amount = 1): self
    {
        for ($i = 0; $i < $amount; $i++) {
            echo PHP_EOL;
        }

        return $this;
    }

    /**
     * Check whether to display the current output or not
     */
    public function verbosityDisallowsOutput(int $verbosity): bool
    {
        if (State::$verbosity >= $verbosity) {
            // Output
            return false;
        }

        // Suppress message
        return true;
    }

    /**
     * Set up your custom tag for output color
     */
    public function registerColor(string $tag, string $code): self
    {
        $this->colors[$tag] = $code;

        return $this;
    }

    /**
     * Remove all registered colors from the given string
     * @see Output::colors
     */

    private function removeTags(string $msg): string
    {
        foreach ($this->colors as $tag => $color) {
            $msg = str_replace("<$tag>", '', $msg);
            $msg = str_replace("</$tag>", '', $msg);
        }

        return $msg;
    }

    /**
     * Dissect / replace the lags like <info> with the related / mapped color string
     * I don't give a fuck about mac os here
     */
    private function dissect(string $str): string
    {
        if (State::$colorDisabled) {
            // Disable colored output
            $str = $this->removeTags($str);
        } else {
            // Highlight message
            $str = $this->parseMessage($str);
        }

        return $str;
    }

    private function parseMessage(string $str): string
    {
        $closingAppendage = '_closing';
        $closingAppendageLength = strlen($closingAppendage);
        $offset = 0;
        $hierarchy = [];

        while ($offset < strlen($str)) {
            $matches = [];
            $next = [
                'tag' => '',
                'position' => null
            ];

            // Get the positions of the next color colors
            foreach ($this->colors as $tag => $color) {
                // Opening tag
                $pos = strpos($str, "<$tag>", $offset);
                if ($pos !== false) {
                    $matches[$tag] = $pos;
                }

                // Closing tag
                $pos = strpos($str, "</$tag>", $offset);
                if ($pos !== false) {
                    $matches[$tag . $closingAppendage] = $pos;
                }
            }

            if (!$matches) {
                break;
            }

            // Get the nearest / next tag
            foreach ($matches as $tag => $pos) {
                if ($next['position'] > $pos || $next['position'] === null) {
                    $next['position'] = $pos;
                    $next['tag'] = $tag;

                    $tagLength = strlen($tag);
                    if ($tagLength >= $closingAppendageLength && substr($tag, $tagLength - $closingAppendageLength) === $closingAppendage) {
                        // It's a closing tag
                        $isClosing = true;
                    } else {
                        // It's an opening tag
                        $isClosing = false;
                    }
                }
            }

            if (!$isClosing) {
                // Only add opening colors to hierarchy
                $hierarchy[] = $next['tag'];
            }

            if ($next['tag']) {
                $closingAppendagePos = strlen($next['tag']) - $closingAppendageLength;
                if (substr($next['tag'], $closingAppendagePos) === $closingAppendage) {
                    // It's a closing tag we need to process
                    $tag = substr($next['tag'], 0, $closingAppendagePos);

                    if ($hierarchy) {
                        // Need to pop twice, because I don't want the value of the most recent opening tag, i want the preceding of the most recent tag
                        $previous = array_pop($hierarchy);

                        if ($tag !== $previous) {
                            // Opening and closing colors do not fit each other
                            echo PHP_EOL . PHP_EOL . 'ERROR: Incorrect closing tag </' . $tag . '> for previously opened <' . $previous . '>' . PHP_EOL . PHP_EOL;
                        }
                    }

                    if ($hierarchy) {
                        // Get the color that needs to be inserted, the color that was used before
                        $previous = array_pop($hierarchy);

                        if (!$previous) {
                            // Standard, no preceding unclosed tag found
                            $color = '0';
                        } else {
                            $color = $this->colors[$previous];
                        }
                    } else {
                        // Standard, from now on there's default coloring again
                        $color = '0';
                    }

                    // Remove the closing appendage
                    $colorStr = $this->getColor($color);
                    $offset = $next['position'] + strlen($colorStr);
                    $str = substr_replace(
                        $str,
                        $colorStr,
                        $next['position'],
                        strlen('</' . $tag . '>')
                    );
                } else {
                    // Opening tag
                    $colorStr = $this->getColor($this->colors[$next['tag']]);
                    $offset = $next['position'] + strlen($colorStr);
                    $str = substr_replace(
                        $str,
                        $colorStr,
                        $next['position'],
                        strlen('<' . $next['tag'] . '>')
                    );
                }
            }
        }

        return $str;
    }

    private function getColor(string $add): string
    {
        return "\033[" .  $add . "m";
    }

}
