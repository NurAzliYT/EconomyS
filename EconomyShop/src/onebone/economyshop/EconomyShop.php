<?php

namespace onebone\economyshop;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

use onebone\economyshop\provider\DataProvider;
use onebone\economyshop\provider\YamlDataProvider;
use onebone\economyshop\item\ItemDisplayer;
use onebone\economyshop\event\ShopCreationEvent;
use onebone\economyshop\event\ShopTransactionEvent;

class EconomyShop extends PluginBase implements Listener {
    private $provider;
    private $lang;
    private $queue = [];
    private $tap = [];
    private $removeQueue = [];
    private $placeQueue = [];
    private $canBuy = [];
    private $items = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->loadLanguage();

        $provider = $this->getConfig()->get("data-provider");
        switch (strtolower($provider)) {
            case "yaml":
                $this->provider = new YamlDataProvider($this->getDataFolder() . "Shops.yml", $this->getConfig()->get("auto-save"));
                break;
            default:
                $this->getLogger()->critical("Invalid data provider was given. EconomyShop will be terminated.");
                return;
        }

        $this->registerEvents();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $params): bool {
        switch ($command->getName()) {
            case "shop":
                switch (strtolower(array_shift($params))) {
                    case "create":
                        // Implement logic for creating a shop
                        break;
                    case "remove":
                        // Implement logic for removing a shop
                        break;
                    case "list":
                        // Implement logic for listing shops
                        break;
                }
                return true;
        }
        return false;
    }

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $level = $player->getLevel()->getFolderName();
        $this->canBuy[strtolower($player->getName())] = true;

        if (isset($this->items[$level])) {
            foreach ($this->items[$level] as $displayer) {
                $displayer->spawnTo($player);
            }
        }
    }

    // Implement other event handlers here...

    private function loadLanguage() {
        $langFile = $this->getResource("lang_" . $this->getConfig()->get("lang", "en") . ".json");
        if ($langFile !== null) {
            $this->lang = json_decode(stream_get_contents($langFile), true);
            fclose($langFile);
        }
    }

    private function registerEvents() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    private function getMessage($key, $replacement = []) {
        $key = strtolower($key);
        if (isset($this->lang[$key])) {
            $search = [];
            $replace = [];

            $this->replaceColors($search, $replace);

            $search[] = "%MONETARY_UNIT%";
            // Replace with BedrockEconomy currency symbol
            $replace[] = "BedrockCurrency";

            $replacecount = count($replacement);
            for ($i = 1; $i <= $replacecount; $i++) {
                $search[] = "%" . $i;
                $replace[] = $replacement[$i - 1];
            }

            return str_replace($search, $replace, $this->lang[$key]);
        }
        return "Could not find \"$key\".";
    }

    private function replaceColors(&$search = [], &$replace = []) {
        $colors = [
            "BLACK" => "0",
            "DARK_BLUE" => "1",
            // Add more color mappings here...
        ];

        foreach ($colors as $color => $code) {
            $search[] = "%%" . $color . "%%";
            $search[] = "&" . $code;

            $replace[] = TextFormat::ESCAPE . $code;
            $replace[] = TextFormat::ESCAPE . $code;
        }
    }

    public function onDisable(): void {
        if ($this->provider instanceof DataProvider) {
            $this->provider->close();
        }
    }
}
