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

<p>Includes only the database tables and fields necessary to process registration logs.</p>

<p>To test the registration parser, place one or more registration log files (or anything else) into the reports folder and click the "parse now" button. It will parse each file in the reports folder, make all database entries and/or updates, move each file into an archive folder (processed or malformed), and make a detailed log entry for each file. You should also see a list of processed file names in your browser. If no file names display, no files were processed. To test again, make sure files exist in the reports folder and simply refresh the page. No need to return to home and click the button.</p>

<p>After each test, you might find it helpful to empty all records from all tables. <br>Here's the SQL: <span style="color:blue;">truncate tblNests; truncate tblEvents; truncate tblSensors; truncate tblCommunicators; </span> Be sure you're the only one testing! 

<p>The parser logs are located at: <strong>www/ts/logs/parser/</strong></p>

<p><a href="<?= $this->config->item('base_url')?>parser"><input type="button" value="Parse Now"></a></p>


