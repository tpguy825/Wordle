<?php

namespace tpguy825\Wordle;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;

class Game {
    public string $word = "";
    public bool $playing = false;
    public int $tries = 0;
    public string $full = "";

    public function __construct(Player|ConsoleCommandSender $player, Main $plugin) {
        $this->playing = true;
        $this->tries = 0;
        $this->full = "";
    }

    public function isPlaying(): bool {
        return $this->playing;
    }

    public function getWord(): string {
        return $this->word;
    }

    public function setWord(string $word): void {
        $this->word = $word;
    }
}