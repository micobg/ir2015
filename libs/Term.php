<?php

/**
 * Term (word)
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class Term {

    protected $dbConn;
    
    protected $term;
    protected $id;

    /**
     * Hardcoded stop words in Bulgarian
     */
    protected $stopWords = array ('а', 'автентичен', 'аз', 'ако', 'ала', 'бе', 'без', 'беше', 'би', 'бивш', 'бивша', 'бившо', 'бил', 'била', 'били', 'било', 'благодаря', 'близо', 'бъдат', 'бъде', 'бяха', 'в', 'вас', 'ваш', 'ваша', 'вероятно', 'вече', 'взема', 'ви', 'вие', 'винаги', 'внимава', 'време', 'все', 'всеки', 'всички', 'всичко', 'всяка', 'във', 'въпреки', 'върху', 'г', 'ги', 'главен', 'главна', 'главно', 'глас', 'го', 'година', 'години', 'годишен', 'д', 'да', 'дали', 'два', 'двама', 'двамата', 'две', 'двете', 'ден', 'днес', 'дни', 'до', 'добра', 'добре', 'добро', 'добър', 'докато', 'докога', 'дори', 'досега', 'доста', 'друг', 'друга', 'други', 'е', 'евтин', 'едва', 'един', 'една', 'еднаква', 'еднакви', 'еднакъв', 'едно', 'екип', 'ето', 'живот', 'за', 'забавям', 'зад', 'заедно', 'заради', 'засега', 'заспал', 'затова', 'защо', 'защото', 'и', 'из', 'или', 'им', 'има', 'имат', 'иска', 'й', 'каза', 'как', 'каква', 'какво', 'както', 'какъв', 'като', 'кога', 'когато', 'което', 'които', 'кой', 'който', 'колко', 'която', 'къде', 'където', 'към', 'лесен', 'лесно', 'ли', 'лош', 'м', 'май', 'малко', 'ме', 'между', 'мек', 'мен', 'месец', 'ми', 'много', 'мнозина', 'мога', 'могат', 'може', 'мокър', 'моля', 'момента', 'му', 'н', 'на', 'над', 'назад', 'най', 'направи', 'напред', 'например', 'нас', 'не', 'него', 'нещо', 'нея', 'ни', 'ние', 'никой', 'нито', 'нищо', 'но', 'нов', 'нова', 'нови', 'новина', 'някои', 'някой', 'няколко', 'няма', 'обаче', 'около', 'освен', 'особено', 'от', 'отгоре', 'отново', 'още', 'пак', 'по', 'повече', 'повечето', 'под', 'поне', 'поради', 'после', 'почти', 'прави', 'пред', 'преди', 'през', 'при', 'пък', 'първата', 'първи', 'първо', 'пъти', 'равен', 'равна', 'с', 'са', 'сам', 'само', 'се', 'сега', 'си', 'син', 'скоро', 'след', 'следващ', 'сме', 'смях', 'според', 'сред', 'срещу', 'сте', 'съм', 'със', 'също', 'т', 'тази', 'така', 'такива', 'такъв', 'там', 'твой', 'те', 'тези', 'ти', 'т.н.', 'то', 'това', 'тогава', 'този', 'той', 'толкова', 'точно', 'три', 'трябва', 'тук', 'тъй', 'тя', 'тях', 'у', 'утре', 'харесва', 'хиляди', 'ч', 'часа', 'че', 'често', 'чрез', 'ще', 'щом', 'юмрук', 'я', 'як');

    public function __construct($term) {
        $this->term = $term;
        
        $this->dbConn = dbConn::getInstance();
    }

    /**
     * Save term if it is new and set termId
     */
    public function save() {
        $result = $this->fetch();
        if (!$result) {
            $this->insert();
        }
    }

    protected function fetch() {
        $searchTerm = $this->dbConn->prepare("SELECT id FROM terms WHERE term = '" . $this->term . "'");
        $result = $searchTerm->execute();
        if (!$result) {
            return FALSE;
        }
        
        $termId = $searchTerm->fetchColumn();
        if (!$termId) {
            return FALSE;
        }

        // set termId
        $this->id = $termId;
        
        return TRUE;
    }

    /**
     * Insert the term in db and return the id of the term
     * 
     * @return int termId
     */
    protected function insert() {
        $insertTerm = $this->dbConn->prepare("INSERT INTO terms(term) VALUES ('" . $this->term . "')");
        $result = $insertTerm->execute();

        if ($result) {
            $this->id = $this->dbConn->lastInsertId();
            
            return TRUE;
        } 
        
        return FALSE;
    }

    /**
     * Is the word a stop word
     *
     * @return boolean
     */
    public function isStopWord() {
        return array_search($this->term, $this->stopWords) === FALSE ? FASLE : TRUE;
    }

    /**
     * Term's getter
     * 
     * @return string
     */
    public function getTerm() {
        return $this->term;
    }
    
    /**
     * Term's id getter
     * 
     * @return string
     */
    public function getId() {
        return $this->id;
    }
}
