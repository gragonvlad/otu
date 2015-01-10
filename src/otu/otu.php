<?php

namespace otu;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\permission\ServerOperator;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

class otu extends PluginBase implements Listener {

	//サーバー開始時の処理//プラグインが有効になると実行されるメソッド
	public function onEnable() {
		$this->saveDefaultConfig();
		$this->reloadConfig();
		@mkdir($this->getDataFolder(), 0755, true);
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->otu = new Config($this->getDataFolder() . "otu.yml", Config::YAML);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);//イベント登録
		jail::init();
	}
	//サーバー停止時の処理//プラグインが無効になると実行されるメソッド
	public function onDisable() {
	}
	
	//コマンド処理
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		switch (strtolower($command->getName())) {
			case "otu"://otuコマンド実行時の処理
				if(!isset($args[0])){return false;}//例外回避
				$player = $this->getServer()->getPlayer($args[0]);//プレーヤー名取得
				if($player instanceof Player){//プレーヤーが存在するかをチェック
					if(!$this->otu->exists($player->getName())){//otuされてるかを確認!
						$this->otu->set($player->getName(),"true");//otuリストに追加!
						$this->otu->save();//セーブ
						$xyz = explode(',', $this->config->get("xyz"));//x,x,xを配列に変換
						$v = new Vector3($xyz[0], $xyz[1], $xyz[2]);//座標指定
						$player->teleport($v);//ターゲットを指定した座標へtp!
						$sender->sendMessage("[乙] ( ﾟω^ )ゝ " . $player->getName() . "さんを牢屋へTP!しました");//コマンド実行者にメッセージ
						$player->sendMessage("( ﾟω^ )ゝ 荒らし乙であります！");//ターゲットへのめっせーじ
					}else{//セットされていれば以下の処理
						$this->otu->remove($player->getName());//otuリストから削除
						$this->otu->save();//セーブ
						$sender->sendMessage("[乙] ( ﾟω^ )ゝ {$player->getName()}さんを釈放しました!");//コマンド実行者にメッセージ送信
						$player->sendMessage("[乙] 釈放～");//ターゲットへのめっせーじ
					}
				}else{
					if(!$this->otu->exists($args[0])){//otuされてるかを確認!
						$this->otu->set($args[0],"true");//otuリストに追加!
						$this->otu->save();//セーブ
						$xyz = explode(',', $this->config->get("xyz"));//x,x,xを配列に変換
						$v = new Vector3($xyz[0], $xyz[1], $xyz[2]);//座標指定
						$sender->sendMessage("[乙] ( ﾟω^ )ゝ " . $player->getName() . "さんを牢屋へTP!しました");//コマンド実行者にメッセージ
					}else{//セットされていれば以下の処理
						$this->otu->remove($args[0]);//otuリストから削除
						$this->otu->save();//セーブ
						$sender->sendMessage("[乙] ( ﾟω^ )ゝ {$player->getName()}さんを釈放しました!");//コマンド実行者にメッセージ送信
					}
					$sender->sendMessage("[乙] プレーヤーが存在しません");//コマンド実行者にメッセージ送信
				}
				return true;
			break;
			case "otup"://otupコマンド実行時の処理
				if(!($sender instanceof Player)){$sender->sendMessage("[乙] ゲーム内で実行してください");}
					$x = round($sender->getX(), 1);//コマンド実行者のX座標取得&四捨五入
					$y = round($sender->getY(), 1);//コマンド実行者のY座標取得&四捨五入
					$z = round($sender->getZ(), 1);//コマンド実行者のZ座標取得&四捨五入
					$this->config->set("xyz",$x . "," . $y ."," . $z);//設定ファイルに座標を設定
					$this->config->save();//セーブ
					//コマンド実行者へ設定完了メッセージを送信
					$sender->sendMessage("[乙] 牢屋の座標を x:" . $x . " y:" . $y . " z:" . $z . "に設定しました");
				return true;
			break;
			case "runa"://runaコマンド実行時の処理
				if(!isset($args[0])){return false;}//例外回避
				$player = $this->getServer()->getPlayer($args[0]);//プレーヤー名取得
				if($player instanceof Player){//プレーヤーが存在するかをチェック
					if(!$this->otu->exists($player->getName())){//outされてるかを確認!
						$sender->sendMessage("[乙] otuされていません");//コマンド実行者にメッセージ送信
					}else{//セットされていれば以下の処理
						if($this->otu->get($player->getName()) == "blocked"){//blockedになってるか
							$this->otu->set($player->getName(),"true");//runaリストから削除
							$this->otu->save();//セーブ
							$sender->sendMessage("[乙] ( ﾟω^ )ゝ {$player->getName()}さんをrunaリストから削除しました!");//コマンド実行者にメッセージ送信
                            $player->sendMessage("[乙] runaを解除しました");//ターゲットへのめっせーじ
						}else{//なっていなければ以下の処理
							$this->otu->set($player->getName(),"blocked");//runa化
							$this->otu->save();//セーブ
							$sender->sendMessage("[乙] ( ﾟω^ )ゝ " . $player->getName() . "さんを動けなくしました");//コマンド実行者にメッセージ
							$player->sendMessage("[乙] 動くと罪が重くなりますよ!");//ターゲットへのめっせーじ
						}
					}
				}else{
					$sender->sendMessage("[乙] プレーヤーが存在しません");//コマンド実行者にメッセージ送信
				}
				return true;
			break;
            case "otulist"://otulistコマンド実行時の処理
				$otulist = $this->otu->getAll();//otuリストを配列で取得
				if(count($otulist) == 0){//otuリストの配列の数を取得し配列があるかをチェック
					$sender->sendMessage("[乙] 現在乙された人はいません");//コマンド実行者にメッセージ送信
					return true;
				}
            	$list = "---otu&runaリスト---\n";//最初のメッセージ
				$count = 0;
				foreach($otulist as $key => $value){//取得したotuリストの配列からキーと値を取得しループ
					$oturuna = ($value == "blocked") ? "ルナ" : "乙";
					if($count >= 3){//リストを見やすくするための条件分岐(少し説明しずらい...)
						$oturuna = ($value == "blocked") ? "ルナ" : "乙";
						//取得した値からルナか乙かを判定しわかりやすく表示&リストに追加(値がblockedの場合はルナ、それ以外の場合は乙と表示されます)
						$list .= $key . "(" . $oturuna . "),\n";
						$count = 0;//リストを見やすくするための変数をリセット
					}else{
						//取得した値からルナか乙かを判定しわかりやすく表示&リストに追加(値がblockedの場合はルナ、それ以外の場合は乙と表示されます)
						$list .= $key . "(" . $oturuna . "),";
						++$count;//リストを見やすくするための変数に追加
					}
				}
				$list = trim($list, ',');//前後の,を削除
				$sender->sendMessage($list);//リストをメッセージ
				return true;
			break;
			case "jail"://jailコマンド実行時の処理
				if(!isset($args[0])){return false;}//例外回避
				$player = $this->getServer()->getPlayer($args[0]);//プレーヤー名取得
				if($player instanceof Player){//プレーヤーが存在するかをチェック
					jail::getInstance()->playerJail($player,$sender,$args[1]);//jail.phpで処理!
					if($sender instanceof Player){
						$sender->sendMessage("[乙] ( ｀･ω ･´)ゞ " . $player->getName() . "さんを牢屋に入れました!");//コマンド実行者にメッセージ
					}else{
						$sender->sendMessage("[乙] (｀･ω･´)ゞ " . $player->getName() . "さんを牢屋に入れました!");//コマンド実行者にメッセージ
					}
					$player->sendMessage("[乙] ( ｀･ω ･´)ゞ 荒らし乙なのであります!");//ターゲットへのめっせーじ
				}else{
					$sender->sendMessage("[乙] プレーヤーが存在しません");//コマンド実行者にメッセージ送信
				}
				return true;
			break;
			case "unjail"://jailコマンド実行時の処理
				if(jail::getInstance()->unJail($sender)){//jail.phpで処理
					if($sender instanceof Player){
						$sender->sendMessage("[乙] ( ｀･ω ･´)ゞ 牢屋を撤去しました");//コマンド実行者にメッセージ
					}else{
						$sender->sendMessage("[乙] (｀･ω･´)ゞ 牢屋を撤去しました");//コマンド実行者にメッセージ
					}
				}else{
					$sender->sendMessage("[乙] 戻すためのデータがありません");//コマンド実行者にメッセージ送信
				}
				return true;
			break;
			case "jailcraft"://jailコマンド実行時の処理
				if(!isset($args[0])){return false;}//例外回避
				switch ($args[0]) {
				case "pos1":
					jail::getInstance()->pos[$sender->getName()][1] = array("x" => $sender->getX(), "y" => $sender->getY(), "z" => $sender->getZ());
					$sender->sendMessage("[乙] 始点を設定しました");//コマンド実行者にメッセージ送信
					break;
				case "pos2":
					Jail::getInstance()->pos[$sender->getName()][2] = array("x" => $sender->getX(), "y" => $sender->getY(), "z" => $sender->getZ());
					$sender->sendMessage("[乙] 終点を設定しました");//コマンド実行者にメッセージ送信
					break;
				case "pos3":
					Jail::getInstance()->pos[$sender->getName()][3] = array("x" => $sender->getX(), "y" => $sender->getY(), "z" => $sender->getZ());
					$sender->sendMessage("[乙] プレーヤーの場所を設定しました");//コマンド実行者にメッセージ送信
					break;
				case "craft":
					if(!isset($args[1])){return false;}//例外回避
					if(isset(Jail::getInstance()->pos[$sender->getName()][1]) and isset(Jail::getInstance()->pos[$sender->getName()][2]) and isset(Jail::getInstance()->pos[$sender->getName()][3])){
						if(Jail::getInstance()->craftJail($sender,$args[1])){
							$sender->sendMessage("[乙] 作成完了!");//コマンド実行者にメッセージ送信
						}else{
							$sender->sendMessage("[乙] エラーだよん");//コマンド実行者にメッセージ送信
						}
					}else{
						$sender->sendMessage("[乙] 始点と終点とプレーヤの場所を指定してください");//コマンド実行者にメッセージ送信
					}
					break;
				default:
					echo "変数sampleは1～3以外です。";
				}
				return true;
			break;
		}
		return false;
	}
	
	//コマンド制限
	public function onPlayerCommand(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		if($this->otu->exists($player->getName())){
			$s = strpos($event->getMessage(), '/');
			if($s == 0 and $s !== false){
				$event->setCancelled(true);
			}
		}
	}
	//移動制限
	public function onPlayerMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if($this->otu->exists($player->getName())){
			if($this->otu->get($player->getName()) == "blocked"){
				$event->setCancelled();
			}
		}
    }
	
	//ブロックタッチ制限
    public function onPlayerInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		if($this->otu->exists($player->getName())){
			$event->setCancelled();
		}
    }
	
	//ブロック破壊制限
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if($this->otu->exists($player->getName())){
			$event->setCancelled();
		}
	}

	//ブロック設置制限
    public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($this->otu->exists($player->getName())){
			$event->setCancelled();
		}
	}
}