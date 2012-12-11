<?php
$query = "
  INSERT
  INTO `user`
  (`user_id`, `email`)
  VALUES
  (?, ?);
";
$stmt = $this->_pdo->prepare($query);
$stmt->execute(array(2, 'person2@example.com'));
