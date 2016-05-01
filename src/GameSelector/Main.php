<?php
# GameSelector v0.3
namespace GameSelector;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\CustomInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseTransaction;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag; 
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\tile\Tile;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as c;

class Main extends PluginBase implements Listener{
	
	public $mode=0;
	public $name="";
	public $prefix="§8[§dGame§aSelector§8]§r";
	
	public function OnEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->info($this->prefix.c::GREEN."GameSelector has been Enabled!");
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::JSON);
		$this->config->save();
	}
	
	public function OnCommand(CommandSender $s, Command $cmd, $label, array $args){
		if(!isset($args[0])){unset($sender,$cmd,$label,$args);return false;};
		switch($args[0]){
			case "add":
			    if((!empty($args[1])) and !($this->config->getNested("Selectors.$args[1]"))){
					$this->name=$args[1];
					$this->config->save();
					$s->sendMessage($this->prefix.c::GREEN."You select the entity now!");
					$this->mode=1;
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs add <name>");
				}
				break;
		    case "additem":
			    if($this->config->getNested("Selectors.$args[1]") and (!empty($args[1])) and (!empty($args[2])) and (!empty($args[3])) and (!empty($args[4])) and $args[2]>=0){
					$pos=$this->config->getNested("Selectors.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$chest->getInventory()->addItem(Item::get($args[2], $args[3], $args[4]));
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::GOLD.$args[2]." ID's item was added from the ".$args[1]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs additem <SELECTORNAME> <Item ID> <damage> <count>");
				}
				break;
		    case "setname":
			    if($this->config->getNested("Selectors.$args[1]") and (!empty($args[1])) and (!empty($args[2]))){
					$pos=$this->config->getNested("Selectors.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$this->config->setNested("Selectors.$args[1].SelectorName", $args[2]);
					$this->config->save();
					$chest->setName($args[2]);
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::YELLOW.$args[1]."'s name was changed to ".$args[2]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs setname <Selectorname> <name>");
				}
				break;
		    case "removeitem":
			case "deleteitem":
			case "delitem":
			    if($this->config->getNested("Selectors.$args[1]") and (!empty($args[1])) and (!empty($args[2])) and $args[2]>=0){
					$pos=$this->config->getNested("Selectors.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$chest->getInventory()->removeItem(Item::get($args[2]));
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::RED.$args[2]." ID's item was removed from the ".$args[1]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs additem <SELECTORNAME> <Item ID> <damage> <count>");
				}
				break;
			case "setcommand":
			    $s->sendMessage($this->prefix.c::RED."This section(ItemCommands) has not been completed! Coming soon...");
				break;
		}
	}
	
	public function OnDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$player=$event->getDamager();
			$entity=$event->getEntity();
			if($player instanceof Player and $this->mode==1){
				$event->setCancelled(true);
			        $x=round($entity->getX());
			        $y=round($entity->getY() - 3);
			        $z=round($entity->getZ());
					$player->getLevel()->setBlock(new Vector3($x, $y, $z), Block::get(54));
                    $chest = new Chest($player->getLevel()->getChunk($x >> 4, $z >> 4, true), new CompoundTag(false, array(new IntTag("x", $x), new IntTag("y", $y), new IntTag("z", $z), new StringTag($this->name))));
					$chest->setName($this->name);
			        $player->getLevel()->addTile($chest);
				   $chest2=new ChestInventory($player->getLevel()->getTile(new Vector3($x, $y, $z)), $player);
				   $ch=$player->getLevel()->getTile(new Vector3($x, $y, $z));
				   $n=$this->name;
				   $ch->saveNBT();
				   $level=$player->getLevel()->getFolderName();
				   $this->config->setNested("Selectors.$n", ["x"=>$x, "y"=>$y, "z"=>$z, "level"=>$level, "Items"=>new ListTag("Items",$ch->getInventory()), "SelectorName"=>$n]);
				   $this->config->setAll($this->config->getAll());
				   $this->config->save();
				   $player->sendMessage($this->prefix.c::GRAY."Entity Selected!");
				   $this->mode=0;
			}
			$x=round($entity->getX());
			        $y=round($entity->getY() - 3);
			        $z=round($entity->getZ());
			if($player->getLevel()->getTile(new Vector3($x, $y, $z))){
				$event->setCancelled(true);
				$chest=$player->getLevel()->getTile(new Vector3($x, $y, $z));
				$player->addWindow($chest->getInventory());
			}
		}
	}
	
	public function InventoryTransactionEvent(InventoryTransactionEvent $event){
		$Transaction = $event->getTransaction();
		$Player = null;
		$BuyingTile = null;
		$chest = null;
		foreach ($Transaction->getInventories() as $inv) {
			if ($inv instanceof PlayerInventory)
				$Player = $inv->getHolder();
			elseif (($inv instanceof BuyingInventory) || ($inv instanceof ChestInventory))
				$chest = $inv->getHolder();
		}
		foreach ($Transaction->getTransactions() as $t) {
			foreach ($this->traderInvTransaction($t) as $nt)
				$added [] = $nt;
		}
		$SourceItem = $added[0]->getSourceItem();
		$TargetItem = $added[0]->getTargetItem();
		if($this->config->getAll()[$chest->getName()] and $SourceItem->getId()>=0){
			$Player->sendMessage($this->prefix.c::RED."This section(ItemCommands) has not been completed! Coming soon...");
			$event->setCancelled(true);
		}
	}
	
	public function traderInvTransaction($t)
	{
		$src = clone $t->getSourceItem();
		$dst = clone $t->getTargetItem();
		if ($dst->getId() == Item::AIR)
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $t->getTargetItem(), clone $src)];
		if ($src->getId() == Item::AIR)
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $dst, clone $src)];
		if ($dst->getCount() > 0) {
			$dst->setCount(1);
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $t->getTargetItem(), clone $dst)];
		}
		return [];
	}
}