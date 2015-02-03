<?php

/**
 * Document to be analyzed
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class Document {
    
    protected $dbConn;

    protected $id;
    protected $fileName;
    protected $title;
    protected $content;
    protected $encoding;

    protected $termsList;

    /**
     * Hardcoded stop words in Bulgarian
     */
    protected $stopWords = array ('а', 'автентичен', 'аз', 'ако', 'ала', 'бе', 'без', 'беше', 'би', 'бивш', 'бивша', 'бившо', 'бил', 'била', 'били', 'било', 'благодаря', 'близо', 'бъдат', 'бъде', 'бяха', 'в', 'вас', 'ваш', 'ваша', 'вероятно', 'вече', 'взема', 'ви', 'вие', 'винаги', 'внимава', 'време', 'все', 'всеки', 'всички', 'всичко', 'всяка', 'във', 'въпреки', 'върху', 'г', 'ги', 'главен', 'главна', 'главно', 'глас', 'го', 'година', 'години', 'годишен', 'д', 'да', 'дали', 'два', 'двама', 'двамата', 'две', 'двете', 'ден', 'днес', 'дни', 'до', 'добра', 'добре', 'добро', 'добър', 'докато', 'докога', 'дори', 'досега', 'доста', 'друг', 'друга', 'други', 'е', 'евтин', 'едва', 'един', 'една', 'еднаква', 'еднакви', 'еднакъв', 'едно', 'екип', 'ето', 'живот', 'за', 'забавям', 'зад', 'заедно', 'заради', 'засега', 'заспал', 'затова', 'защо', 'защото', 'и', 'из', 'или', 'им', 'има', 'имат', 'иска', 'й', 'каза', 'как', 'каква', 'какво', 'както', 'какъв', 'като', 'кога', 'когато', 'което', 'които', 'кой', 'който', 'колко', 'която', 'къде', 'където', 'към', 'лесен', 'лесно', 'ли', 'лош', 'м', 'май', 'малко', 'ме', 'между', 'мек', 'мен', 'месец', 'ми', 'много', 'мнозина', 'мога', 'могат', 'може', 'мокър', 'моля', 'момента', 'му', 'н', 'на', 'над', 'назад', 'най', 'направи', 'напред', 'например', 'нас', 'не', 'него', 'нещо', 'нея', 'ни', 'ние', 'никой', 'нито', 'нищо', 'но', 'нов', 'нова', 'нови', 'новина', 'някои', 'някой', 'няколко', 'няма', 'обаче', 'около', 'освен', 'особено', 'от', 'отгоре', 'отново', 'още', 'пак', 'по', 'повече', 'повечето', 'под', 'поне', 'поради', 'после', 'почти', 'прави', 'пред', 'преди', 'през', 'при', 'пък', 'първата', 'първи', 'първо', 'пъти', 'равен', 'равна', 'с', 'са', 'сам', 'само', 'се', 'сега', 'си', 'син', 'скоро', 'след', 'следващ', 'сме', 'смях', 'според', 'сред', 'срещу', 'сте', 'съм', 'със', 'също', 'т', 'тази', 'така', 'такива', 'такъв', 'там', 'твой', 'те', 'тези', 'ти', 'т.н.', 'то', 'това', 'тогава', 'този', 'той', 'толкова', 'точно', 'три', 'трябва', 'тук', 'тъй', 'тя', 'тях', 'у', 'утре', 'харесва', 'хиляди', 'ч', 'часа', 'че', 'често', 'чрез', 'ще', 'щом', 'юмрук', 'я', 'як');

    public function __construct($filename) {
        $this->dbConn = dbConn::getInstance();
        $this->fileName = $filename;
        
        // get files content
        $this->getFileContent();

        // insert the document in db
        $this->insert();
        
        // init terms list
        $this->termsList = new TermsList();
        
        // save terms and relations
        $this->manageMatches();
    }
    
    /**
     * Insert document in db
     */
    protected function insert() {
        $this->title = strtok($this->content, "\n");
        
        $insertDoc = $this->dbConn->prepare(""
            . "INSERT INTO docs(file_name, title) "
            . "VALUES ('" . $this->fileName . "', '" . $this->title . "')");
        $result = $insertDoc->execute();

        if ($result) {
            $this->id = $this->dbConn->lastInsertId();
        } else {
            throw new Exception('Error on insert document in database.', 500);
        }
    }

    /**
     * Get file content
     * 
     * @throws Exception on error or empty file
     */
    protected function getFileContent() {
        $this->content = file_get_contents($this->fileName);
        if (!$this->content) {
            throw new Exception('The file does not exist or it is empty.', 404);
        }
    }
    
    /**
     * Iterate over all matches (words) in the document and save terms and their
     * relations.
     */
    protected function manageMatches() {
        $matches = $this->extractTerms();
        foreach ($matches as $word) {
            // normalize
            $word = mb_strtolower($word, $this->encoding);

            // skip stop words
            $termObj = new Term($word);
            if ($termObj->isStopWord()) {
                continue;
            }

            // save the term
            $termObj->save();
            $this->termsList->insert($termObj);
            
            // TODO: save term-doc relation (inverted index)
            
            unset($termObj);
        }
        unset($word);
    }

    /**
     * Find all potential terms from the document
     */
    protected function extractTerms() {        
        // get all words in the file
        $matches = array();
        preg_match_all('/\w*/iu', $this->content, $matches);
        
        $this->encoding = mb_detect_encoding($this->content);
        
        return array_unique(array_filter($matches[0]));
    }
    
}