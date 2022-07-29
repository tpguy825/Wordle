<?php

declare(strict_types=1);

namespace tpguy825\Wordle;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Internet;
use pocketmine\player\Player;

class Main extends PluginBase {
    /**
     * @var string $prefix Chat message prefix
     * @var string $version Plugin version
     * @var string $word Word to guess
     * @var bool $playing Is the game being played?
     * @var array $list Word list
     * @var array[Game] $games Array of games
     * @var int $tries Number of words guessed in that game
     * @var string green Green block
     * @var string yellow Yellow block
     * @var string grey Grey block
     * @var string cgreen Green block (console)
     * @var string cyellow Yellow block (console)
     * @var string cgrey Grey block (console)
     */
    private string $prefix = TextFormat::GREEN."[Wordle] ";
    private string $version = "1.0.0";
    private array $list = [];
    private array $games = [];
    private const green = TextFormat::GREEN."⬛️";
    private const yellow = TextFormat::YELLOW."⬛️";
    private const grey = TextFormat::GRAY."⬛️";
    private const cgreen = TextFormat::GREEN."⬛";
    private const cyellow = TextFormat::YELLOW."⬛️";
    private const cgrey = TextFormat::GRAY."⬛️";

    public function onEnable(): void {
        $this->getLogger()->info(TextFormat::GREEN."Enabled!");
        $this->getLogger()->info(TextFormat::GREEN."Version: " . $this->version);
        $this->list = explode("\n", Internet::getURL("https://raw.githubusercontent.com/tpguy825/Wordle/master/words")->getBody());
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch($command->getName()) {
            case "wordle"or "wd":
                if(isset($args[0])) {
                    if($args[0] === "start") {
                        if($sender->hasPermission("wordle.command.wordle.start")) {
                            $this->games[$sender->getName()] = new Game($sender, $this);
                            $this->word = $this->generateword($sender);
                            $sender->sendMessage($this->prefix . TextFormat::GREEN."Wordle started! Make a guess using /wordle guess <word>");
                        } else {
                            $sender->sendMessage($this->prefix . TextFormat::RED."You don't have permission to use this command!");
                        }
                    } elseif($args[0] === "stop") {
                        if($sender->hasPermission("wordle.command.wordle.stop")) {
                            unset($this->games[$sender->getName()]);
                            $sender->sendMessage($this->prefix . TextFormat::GREEN."Wordle stopped!");
                        } else {
                            $sender->sendMessage($this->prefix . TextFormat::RED."You don't have permission to use this command!");
                        }
                    } elseif($args[0] === "g" or $args[0] === "guess") {
                        if($sender->hasPermission("wordle.command.wordle.guess")) {
                            if(isset($args[1]) and $args[1] !== "") {
                                $this->guess($args[1], $sender);
                            } else {
                                $sender->sendMessage($this->prefix . TextFormat::RED."Usage: /wordle guess <word>");
                            }
                        } else {
                            $sender->sendMessage($this->prefix . TextFormat::RED."You don't have permission to use this command!");
                        }
                    } elseif($args[0] === "showme" and $sender instanceof ConsoleCommandSender) {
                        if(isset($this->games[$sender->getName()])) {
                            $this->getLogger()->info(Textformat::GREEN."Your word is ".$this->games[$sender->getName()]->word);
                        } else {
                            $sender->sendMessage($this->prefix . TextFormat::RED."You aren't playing a game!");
                        }
                    } else {
                        $sender->sendMessage($this->prefix . TextFormat::RED."Usage: /wordle <start|stop|guess|g>");
                    }
                } else {
                    $sender->sendMessage($this->prefix . TextFormat::RED."Usage: /wordle <start|stop|guess|g>");
                }
                return true;
            default:
                return false;
        }
    }

    public function generateword(Player|ConsoleCommandSender $sender) {
        $this->getLogger()->info(TextFormat::GREEN.TextFormat::GREEN."Generating word...");
        $word = $this->getwordasarray();
        $lettercount = array_unique($word);
        while(count($lettercount) < 5) {
            $lettercount = array_unique($this->getwordasarray());
        }
        $this->games[$sender->getName()]->setword(implode($lettercount));
        if($sender instanceof Player) {
            $this->getLogger()->info(TextFormat::GREEN.$sender->getName()."'s Word: " . implode($lettercount));
        } else {
            $this->getLogger()->info(TextFormat::GREEN.$sender->getName()."'s Word: **Hidden, use '/wd showme' to show it**");
        }
    }

    public function getwordasarray() {
        return str_split($this->list[rand(0, count($this->list) - 1)]);
    }

    public function guess(string $guess, Player|ConsoleCommandSender $sender) {
        if(strlen($guess) === 5) {
            if($this->games[$sender->getName()]->isplaying()) {
                /** 
                 * @var Game $game
                */
                $game = $this->games[$sender->getName()];
                if($game->tries < 6) {
                    if($guess === $this->games[$sender->getName()]->getWord()) {
                        $sender->sendMessage($this->prefix . TextFormat::GREEN."You guessed the word! It was " . $game->word);
                        $sender->sendMessage($this->prefix . TextFormat::GREEN."It took you " . $game->tries . " tries!");
                        if($sender instanceof Player) {
                            $this->getLogger()->info(TextFormat::GREEN."Player " . $sender->getName() . " guessed the word! It was " . $game->word);
                            $this->getLogger()->info(TextFormat::GREEN."It took them " . $game->tries . ($game->tries === 1 ? " try!" : " tries!"));
                            $game->full .= "\n".TextFormat::GREEN.implode(" ", str_split($game->word));
                            $game->full = str_replace(Main::green, Main::cgreen, $game->full);
                            $game->full = str_replace(Main::yellow, Main::cyellow, $game->full);
                            $game->full = str_replace(Main::grey, Main::cgrey, $game->full);
                            $this->getLogger()->info(TextFormat::GREEN."Full guesses: " . $game->full);
                        }
                        unset($game);
                        unset($this->games[$sender->getName()]);
                    } else {
                        $game->tries++;
                        if($game->tries === 6) {
                            $sender->sendMessage($this->prefix . TextFormat::RED."You lost! The word was " . $game->word);
                            unset($game);
                            unset($this->games[$sender->getName()]);
                        } else {
                            $guess = str_split($guess);
                            $word = str_split($game->word);
                            $result = "";
                            foreach($guess as $key => $letter) {
                                if($letter === $word[$key]) {
                                    // $result .= "🟩";
                                    $result .= Textformat::GREEN.$letter." ";
                                } elseif(str_contains($game->word, $letter)) {
                                    // $result .= "🟨";
                                    $result .= TextFormat::YELLOW.$letter." ";
                                } else {
                                    // $result .= "⬛️";
                                    $result .= TextFormat::GRAY.$letter." ";
                                }
                            }
                            $sender->sendMessage($this->prefix . TextFormat::GREEN."You guessed: ".TextFormat::BOLD.$result);
                            $sender->sendMessage($this->prefix . TextFormat::GREEN."You have " . (6 - $game->tries) . " attempts left!");
                            $game->full .= "\n$result";
                        }
                    }
                } else {
                    $sender->sendMessage($this->prefix . TextFormat::RED."You lost! The word was " . $game->word);
                    unset($game);
                    unset($this->games[$sender->getName()]);
                }
            } else {
                $sender->sendMessage($this->prefix . TextFormat::RED."Not playing! Start a game using /wordle start");
            }
        } else {
            $sender->sendMessage($this->prefix . TextFormat::RED."Invalid word! Word must be 5 letters long!");
        }
    }
}
