<?php

namespace tpguy825\Wordle;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Game {
    public string $word = "";
    public bool $playing = false;
    public int $tries = 0;
    public string $full = "";

    public function __construct(Player $player, Main $plugin) {
        $this->playing = true;
        $this->word = $plugin->generateword($player);
        $this->tries = 0;
        $this->full = "";
        $player->sendMessage($plugin->prefix . TextFormat::GREEN."Wordle started! Make a guess using /wordle guess <word>");
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