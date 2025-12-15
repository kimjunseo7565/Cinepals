<?php
function filter_profanity($text) {
    $con = mysqli_connect("localhost", "root", "", "moviedb");
    
    if (!$con) {
        return $text;
    }
    
    // 긴 단어부터 처리 (ORDER BY LENGTH DESC 추가)
    $sql = "SELECT word FROM filter_words ORDER BY LENGTH(word) DESC";
    $result = mysqli_query($con, $sql);
    
    $filtered_text = $text;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bad_word = $row['word'];
        $replacement = str_repeat('*', mb_strlen($bad_word, 'UTF-8'));
        
        // 대소문자 구분없이 교체
        $filtered_text = str_ireplace($bad_word, $replacement, $filtered_text);
    }
    
    mysqli_close($con);
    return $filtered_text;
}
?>