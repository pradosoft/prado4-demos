<?php 

class QuickstartIndex
{
	private $_index;
	private $_dir;
	private $_base;
	public function __construct($index_file, $base)
	{
		$this->_index = new Zend_Search_Lucene($index_file, true);
		$this->_dir = $index_file;
		$this->_base = $base;
	}
	public function create_index()
	{
		echo "Building search index...\n";
		$pages = glob($this->_base.'/*/*.page');
		$count = 0;
		foreach($pages as $page)
		{
			echo "    Adding $page\n";
			$file_link = basename(dirname($page)) . '.' . str_replace('.page', '', basename($page));
			$file_content = file_get_contents($page);
			$this->add($file_content, $file_link, filemtime($page));
			$count++;
		}
		$this->_index->commit();
		echo "\n {$count} files indexed.\n";
	}
	public function add($content, $file_link, $mtime)
	{
		foreach($this->split_headings($content) as $headers)
		{
			$doc = new Zend_Search_Lucene_Document();
			$link = "index.php?page=" . $file_link . '#' . $headers['section'];
			//unsearchable text
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('link', $link));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('mtime', $mtime));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('title', $headers['title']));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('text', $headers['content']));
			//searchable text
			$doc->addField(Zend_Search_Lucene_Field::Keyword('page', strtolower($headers['title'])));
			$body = strtolower($this->sanitize($headers['content'])).' '.strtolower($headers['title']);
			$doc->addField(Zend_Search_Lucene_Field::Unstored('contents',$body));
			$this->_index->addDocument($doc);
		}
	}
	function sanitize($input)
	{
		return htmlentities(strip_tags( $input ));
	}
	public function index()
	{
		return $this->_index;
	}
	protected function split_headings($html)
	{
		$html = preg_replace('/<\/?com:TContent[^<]*>/', '', $html);
		$html = preg_replace('/<b>([^<]*)<\/b>/', '$1', $html);
		$html = preg_replace('/<i>([^<]*)<\/i>/', '$1', $html);
		$html = preg_replace('/<tt>([^<]*)<\/tt>/', '$1', $html);
		$html = preg_replace('/<h1([^>]*)>([^<]*)<\/h1>/', '<hh$1>$2</hh>', $html);
		$html = preg_replace('/<h2([^>]*)>([^<]*)<\/h2>/', '<hh$1>$2</hh>', $html);
		$html = preg_replace('/<h3([^>]*)>([^<]*)<\/h3>/', '<hh$1>$2</hh>', $html);
		$sections = preg_split('/<hh[^>]*>([^<]+)<\/hh>/', $html,-1);
		$headers = array();
		preg_match_all('/<hh([^>]*)>([^<]+)<\/hh>/', $html, $headers);
		$contents = array();
		for($i = 1, $t = count($sections); $i < $t; $i++)
		{
			$content['title'] = trim($this->sanitize($headers[2][$i-1]));
			$content['section'] = str_replace('"', '',trim($headers[1][$i-1],'"'));
			$content['content'] = trim($this->sanitize($sections[$i]));
			$contents[] = $content;
		}
		return $contents;
	}
	public function commit()
	{
		$this->_index->commit();
		$count = $this->_index->count();
		echo "\nSaving search index ({$count}) to {$this->_dir}\n\n";
	}
}

require '../../../vendor/autoload.php';

$destdir = realpath(__DIR__.'/quickstart/');
array_map('unlink', glob($destdir . "/*"));
$quickstart_base = realpath(__DIR__.'/../pages/');
$quickstart = new QuickstartIndex($destdir, $quickstart_base);
$quickstart->create_index();
