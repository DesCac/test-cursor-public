<?php

namespace App\DataFixtures;

use App\Entity\DialogConnection;
use App\Entity\DialogNode;
use App\Entity\NPC;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NPCFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create "Elder Sage" NPC with complex branching dialog
        $elderSage = new NPC();
        $elderSage->setName('Elder Sage');
        $elderSage->setDescription('A wise old sage with ancient knowledge and complex quests');
        $manager->persist($elderSage);

        // Start node
        $start = $this->createNode($manager, $elderSage, 'start', 
            'Приветствую тебя, путник. Я чувствую в тебе силу...', 100, 50);
        
        // First branch - greeting
        $greeting = $this->createNode($manager, $elderSage, 'dialog',
            'Ты пришел в нужное время. Наш город в опасности, и только избранный может помочь.', 100, 200);
        
        // Player choice: interested or not
        $askMore = $this->createNode($manager, $elderSage, 'dialog',
            'Тёмные силы пробудились в древних руинах на севере. Монстры становятся сильнее с каждым днём.', 
            -150, 350);
        
        $notInterested = $this->createNode($manager, $elderSage, 'dialog',
            'Понимаю твою осторожность. Но подумай - награда будет щедрой...', 
            150, 350);
        
        // Condition check - player level
        $levelCheck = $this->createNode($manager, $elderSage, 'condition',
            'Проверка уровня героя', -150, 500, ['level' => ['min' => 5]]);
        
        // If level too low
        $tooWeak = $this->createNode($manager, $elderSage, 'dialog',
            'Увы, ты ещё слишком слаб для этого задания. Вернись, когда станешь сильнее.', 
            -300, 650);
        
        // If level sufficient
        $questDetails = $this->createNode($manager, $elderSage, 'dialog',
            'Отлично! Твой уровень подходит. Вот что тебе нужно сделать: найди древний артефакт в руинах и уничтожь источник тьмы.', 
            0, 650);
        
        // Player accepts or declines
        $acceptQuest = $this->createNode($manager, $elderSage, 'action',
            'Да пребудет с тобой сила! Вот карта руин и волшебный амулет для защиты.', 
            -200, 800);
        
        $declineQuest = $this->createNode($manager, $elderSage, 'dialog',
            'Жаль... Но если передумаешь - знаешь где меня найти.', 
            100, 800);
        
        // Check if player has special item
        $itemCheck = $this->createNode($manager, $elderSage, 'condition',
            'Проверка наличия древнего ключа', 
            -200, 950, ['inventory' => ['has' => 'ancient_key']]);
        
        // Special path with ancient key
        $secretPath = $this->createNode($manager, $elderSage, 'dialog',
            'О! У тебя есть древний ключ! Тогда я могу рассказать тебе о тайном проходе...', 
            -400, 1100);
        
        $secretReward = $this->createNode($manager, $elderSage, 'action',
            'Ты получаешь легендарное оружие и доступ к секретной области!', 
            -400, 1250);
        
        // Normal quest completion
        $normalCompletion = $this->createNode($manager, $elderSage, 'action',
            'Ты получаешь награду: 1000 золота и опыт.', 
            -50, 1100);
        
        // Multiple endings
        $goodEnding = $this->createNode($manager, $elderSage, 'end',
            'Спасибо, герой! Ты спас наш город! Да прославится твоё имя!', 
            -200, 1400);
        
        $neutralEnding = $this->createNode($manager, $elderSage, 'end',
            'Возможно, когда-нибудь ты вернёшься. До встречи.', 
            200, 950);
        
        $lowLevelEnding = $this->createNode($manager, $elderSage, 'end',
            'Иди, тренируйся и возвращайся. Удачи!', 
            -450, 800);
        
        // Second questline branch from "not interested"
        $mentionReward = $this->createNode($manager, $elderSage, 'dialog',
            'Я предлагаю 5000 золотых монет и магический артефакт невероятной силы.', 
            350, 500);
        
        $nowInterested = $this->createNode($manager, $elderSage, 'dialog',
            'Вижу, это изменило твоё мнение. Тогда присоединяйся к questDetails выше...', 
            350, 650);

        // Create all connections with choice texts and conditions
        $this->connect($manager, $start, $greeting);
        
        $this->connect($manager, $greeting, $askMore, 'Расскажи подробнее об опасности');
        $this->connect($manager, $greeting, $notInterested, 'Меня это не интересует');
        
        $this->connect($manager, $askMore, $levelCheck);
        
        // Conditional branches from level check
        $this->connect($manager, $levelCheck, $tooWeak, 'Уровень < 5', ['level' => ['max' => 4]]);
        $this->connect($manager, $levelCheck, $questDetails, 'Уровень >= 5', ['level' => ['min' => 5]]);
        
        $this->connect($manager, $tooWeak, $lowLevelEnding);
        
        $this->connect($manager, $questDetails, $acceptQuest, 'Я принимаю этот квест!');
        $this->connect($manager, $questDetails, $declineQuest, 'Мне нужно подумать');
        
        $this->connect($manager, $acceptQuest, $itemCheck);
        
        $this->connect($manager, $itemCheck, $secretPath, 'Есть ключ', ['inventory' => ['has' => 'ancient_key']]);
        $this->connect($manager, $itemCheck, $normalCompletion, 'Нет ключа');
        
        $this->connect($manager, $secretPath, $secretReward);
        $this->connect($manager, $secretReward, $goodEnding);
        $this->connect($manager, $normalCompletion, $goodEnding);
        
        $this->connect($manager, $declineQuest, $neutralEnding);
        
        // Branch from "not interested"
        $this->connect($manager, $notInterested, $mentionReward, 'Какая награда?');
        $this->connect($manager, $notInterested, $neutralEnding, 'Нет, спасибо');
        
        $this->connect($manager, $mentionReward, $nowInterested, 'Ну, раз так...');
        $this->connect($manager, $nowInterested, $questDetails);

        // Create "Merchant Bob" NPC with trading dialog
        $merchant = new NPC();
        $merchant->setName('Merchant Bob');
        $merchant->setDescription('A cheerful merchant with various goods and services');
        $manager->persist($merchant);

        $mStart = $this->createNode($manager, $merchant, 'start',
            'Приветствую в моей лавке! Лучшие товары в городе!', 100, 50);
        
        $mMenu = $this->createNode($manager, $merchant, 'dialog',
            'Что тебя интересует?', 100, 200);
        
        $mBuy = $this->createNode($manager, $merchant, 'action',
            'Вот мои товары: зелья, оружие, доспехи...', -100, 350);
        
        $mSell = $this->createNode($manager, $merchant, 'action',
            'Покажи, что у тебя есть. Я куплю всё ценное!', 100, 350);
        
        $mRepair = $this->createNode($manager, $merchant, 'action',
            'Дай посмотрю... Ага, починю за 50 золотых.', 300, 350);
        
        // Gold check for repairs
        $goldCheck = $this->createNode($manager, $merchant, 'condition',
            'Проверка золота', 300, 500, ['currency' => ['gold' => ['min' => 50]]]);
        
        $repairDone = $this->createNode($manager, $merchant, 'action',
            'Готово! Как новенькое!', 200, 650);
        
        $noGold = $this->createNode($manager, $merchant, 'dialog',
            'Эх, у тебя недостаточно денег. Приходи, когда разбогатеешь!', 400, 650);
        
        $mThanks = $this->createNode($manager, $merchant, 'dialog',
            'Спасибо за покупку! Приходи ещё!', 0, 800);
        
        $mGoodbye = $this->createNode($manager, $merchant, 'end',
            'Удачи в приключениях!', 100, 950);

        $this->connect($manager, $mStart, $mMenu);
        $this->connect($manager, $mMenu, $mBuy, 'Хочу купить');
        $this->connect($manager, $mMenu, $mSell, 'Хочу продать');
        $this->connect($manager, $mMenu, $mRepair, 'Можешь починить экипировку?');
        $this->connect($manager, $mMenu, $mGoodbye, 'Просто осматриваюсь');
        
        $this->connect($manager, $mBuy, $mThanks);
        $this->connect($manager, $mSell, $mThanks);
        
        $this->connect($manager, $mRepair, $goldCheck);
        $this->connect($manager, $goldCheck, $repairDone, 'Достаточно золота', ['currency' => ['gold' => ['min' => 50]]]);
        $this->connect($manager, $goldCheck, $noGold, 'Мало золота', ['currency' => ['gold' => ['max' => 49]]]);
        
        $this->connect($manager, $repairDone, $mThanks);
        $this->connect($manager, $noGold, $mGoodbye);
        $this->connect($manager, $mThanks, $mMenu, 'Что-нибудь ещё?');
        $this->connect($manager, $mThanks, $mGoodbye, 'Это всё, спасибо');

        $manager->flush();
    }

    private function createNode(
        ObjectManager $manager, 
        NPC $npc, 
        string $type, 
        string $text, 
        float $x, 
        float $y,
        ?array $conditions = null
    ): DialogNode {
        $node = new DialogNode();
        $node->setNpc($npc);
        $node->setNodeType($type);
        $node->setText($text);
        $node->setPositionX($x);
        $node->setPositionY($y);
        if ($conditions) {
            $node->setConditions($conditions);
        }
        $manager->persist($node);
        return $node;
    }

    private function connect(
        ObjectManager $manager,
        DialogNode $source,
        DialogNode $target,
        ?string $choiceText = null,
        ?array $conditions = null
    ): void {
        $conn = new DialogConnection();
        $conn->setSourceNode($source);
        $conn->setTargetNode($target);
        if ($choiceText) {
            $conn->setChoiceText($choiceText);
        }
        if ($conditions) {
            $conn->setConditions($conditions);
        }
        $manager->persist($conn);
    }
}
