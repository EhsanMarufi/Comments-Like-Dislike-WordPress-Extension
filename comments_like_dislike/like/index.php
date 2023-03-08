<?php
// likes
if(isset($_POST['comment_id']) && preg_match('#^\d+$#', $_POST['comment_id'])) {
	require_once '../core/comments_like_dislike_core.php';
	
	
	$opinion_row = comments_like_dislike::get_opinion_row($_POST['comment_id'], $_SERVER['REMOTE_ADDR']);
	if(empty($opinion_row)) {
		comments_like_dislike::insert_like_opinion($_POST['comment_id'], $_SERVER['REMOTE_ADDR']);
		$opinion_row = comments_like_dislike::get_opinion_row($_POST['comment_id'], $_SERVER['REMOTE_ADDR']);
	}
	
	$opinion_row['insert_date'] = 'You liked this comment at ' . $opinion_row['insert_date'];
	
	$likes_count = comments_like_dislike::count_like_opinions($_POST['comment_id']);
	
	echo json_encode(
		array_merge(
			$opinion_row,
			array(
				'likes_count' => $likes_count
			)
		)
	);
}
