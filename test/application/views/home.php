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

<p>Uses database name "test". Includes only the tables and fields necessary to process registration logs.</p>

<p>Each time you click the "Parse Now" button on this page or refresh the resulting page at <strong>http://root.turtlesense.org/test/parser</strong>, the parser will begin again.</p>

<p>To test the registration parser,place one or more registration log files into <strong>reports_ts</strong> and click "Parse Now" button. It will parse each file in reports_ts, make all database entries/updates, move each file into either reports_processed or reports_malformed, and make a log entry for each file at <strong><?=$this->config->item('parser_logs_dir')?></strong> (filed by date). When the parser completes, a list of processed and malformed file names display in your browser window. If no names display, no files were processed. To test again, make sure files exist in reports_ts and simply refresh the page. No need to return home and click the button.</p>

<p>After each test, you might find it helpful to empty all records from all tables. <br>Here's the SQL: <span style="color:blue;">truncate tblNests; truncate tblEvents; truncate tblSensors; truncate tblCommunicators; </span> Be sure you're the only one testing, or you'll confuse the other tester for sure.


<p><a href="<?= $this->config->item('base_url')?>parser"><input type="button" value="Parse Now"></a></p>


