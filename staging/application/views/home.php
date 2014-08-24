<h1>Turtle Sense</h1>
<h2>Parser and Database Project</h2>

<p>This project is divided into three parts:
<ul>
	<li>Part 1: Registration Parser</li>
	<li>Part 2: Report Parser</li>
	<li>Part 3: Data conversions</li>
</ul>
</p>
<h3>PART 1: Registration Parser</h3>

<p>Uses database name "test".</p>

<p>Click the "Parse Now" button on this page or refresh the resulting page at <strong>http://root.turtlesense.org/staging/parser</strong>, to start the parsing process.</p>

<p>The parser will read all files in the ftp directory that have a modification date newer than the last time it ran. It will make all database entries/updates and write the results to a log at <strong><?=$this->config->item('logs_parser_dir')?></strong> (filed by date).</p>

<p>After each test, you might find it helpful to empty all records from all tables. <br>Here's the SQL: <span style="color:blue;">truncate NESTS; truncate EVENTS; truncate SENSORS; truncate COMMUNICATORS; </span> Be sure you're the only one testing, or you'll confuse the other tester for sure.


<p><a href="<?= $this->config->item('base_url')?>parser"><input type="button" value="Parse Now"></a></p>


