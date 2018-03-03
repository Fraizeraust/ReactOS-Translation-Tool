<?php
/* PROJECT:     ReactOS Translation Tool
 * LICENSE:     GPL
 * AUTHORS:     Adam Stachowicz <saibamenppl@gmail.com>
 * AUTHOR URL:  http://it-maniak.pl/
 */

include_once('header.php');
?>

<h1>Search missing translation strings</h1>

<div id="body">

<?php

require_once('config.php');
?>

<center>
    <form method="GET" class="form-horizontal">
        <fieldset>
            <legend>Please type your <a href="https://beta.wikiversity.org/wiki/List_of_ISO_639-1_codes">language code in ISO 639-1</a>. For example: pl for Polish, de for German</legend>
            <div class="form-group">
                <label class="col-md-4 control-label" for="lang">Language code:</label>
                <div class="col-md-4">
                    <input type="text" value="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : "" ?>" id="lang" name="lang" class="form-control input-md" required="required" autofocus="autofocus" pattern="[A-Za-z]{2}" title="Two letter language code"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label" for="dir">Directories:</label>
                <div class="col-md-4">
                <select id="dir" name="dir" class="form-control">
                    <option value="1">base, boot</option>
                    <option value="2" <?php if(isset($_GET["dir"]) && $_GET["dir"] == '2'){echo("selected");}?>>dll</option>
                    <option value="3" <?php if(isset($_GET["dir"]) && $_GET["dir"] == '3'){echo("selected");}?>>media, subsystems, win32ss</option>
                    <option value="100" <?php if(isset($_GET["dir"]) && $_GET["dir"] == '100'){echo("selected");}?>>All ReactOS Source dir</option>
                </select>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </fieldset>
    </form>
</center>
<br>

<?php
if (isset($_GET["lang"]) && !empty($_GET["lang"]) && isset($_GET["dir"]) && is_numeric($_GET["dir"]))
{
    // Switch for directories
    switch ($_GET["dir"])
    {
        case "1":
            $directory1 = new RecursiveDirectoryIterator($ROSDir. "base/applications");
            $directory2 = new RecursiveDirectoryIterator($ROSDir. "base/setup");
            $directory3 = new RecursiveDirectoryIterator($ROSDir. "base/shell");
            $directory4 = new RecursiveDirectoryIterator($ROSDir. "base/system");
            $directory5 = new RecursiveDirectoryIterator($ROSDir. "boot/freeldr/fdebug");

            $it = new AppendIterator();
            $it->append(new RecursiveIteratorIterator($directory1));
            $it->append(new RecursiveIteratorIterator($directory2));
            $it->append(new RecursiveIteratorIterator($directory3));
            $it->append(new RecursiveIteratorIterator($directory4));
            $it->append(new RecursiveIteratorIterator($directory5));
            break;

        case "2":
            $directory6 = new RecursiveDirectoryIterator($ROSDir. "dll/cpl");
            $directory7 = new RecursiveDirectoryIterator($ROSDir. "dll/shellext");
            $directory8 = new RecursiveDirectoryIterator($ROSDir. "dll/win32");

            $it = new AppendIterator();
            $it->append(new RecursiveIteratorIterator($directory6));
            $it->append(new RecursiveIteratorIterator($directory7));
            $it->append(new RecursiveIteratorIterator($directory8));
            break;

        case "3":
            $directory9 = new RecursiveDirectoryIterator($ROSDir. "media/themes");
            $directory10 = new RecursiveDirectoryIterator($ROSDir. "subsystems/mvdm/ntvdm");
            $directory11 = new RecursiveDirectoryIterator($ROSDir. "win32ss/user");

            $it = new AppendIterator();
            $it->append(new RecursiveIteratorIterator($directory9));
            $it->append(new RecursiveIteratorIterator($directory10));
            $it->append(new RecursiveIteratorIterator($directory11));
            break;

        // Search in source dir - only for test
        case "100":
            $directory = new RecursiveDirectoryIterator($ROSDir);

            $it = new AppendIterator();
            $it->append(new RecursiveIteratorIterator($directory));
            break;

        default:
            echo "Something is wrong! Please try again.";
            exit;
    }

    function diff_versions($leftContent, $rightContent)
    {
        $leftVersion = $rightVersion = null;

        // FIXME: Search multi-line with ""some text""
        $pattern = "/^(?!FONT|\\s*\\*|\\#\\include|\\s*\\ICON)[^\"\\n]*\"\\K(?!\\s*(?:\"|\\n))([^\"]+)/m";

        if (preg_match_all($pattern, $leftContent, $matches) <= 0)
        {
            throw new Exception("Left content has no version line.");
        }

        $leftVersion = $matches[1];

        if (preg_match_all($pattern, $rightContent, $matches) <= 0)
        {
            throw new Exception("Right content has no version line.");
        }

        $rightVersion = $matches[1];

        return array(
            'diff' => array_intersect($leftVersion, $rightVersion),
            'leftVersion' => $leftVersion,
            'rightVersion' => $rightVersion,
        );
    }

    function exceptions_error_handler($severity, $message, $filename, $lineno)
    {
        if (error_reporting() == 0)
        {
            return;
        }
        if (error_reporting() & $severity)
        {
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
        }
    }

    set_error_handler('exceptions_error_handler');

    $regex = new RegexIterator($it, '/^.+'. $langDir .'.+('. $originLang .')\.'. $fileExt .'$/i', RecursiveRegexIterator::GET_MATCH);

    $missing = $allStrings = 0;

    $lang = htmlspecialchars($_GET["lang"]);
    // Search for eg. PL,Pl,pl
    $fileSearch = strtoupper($lang) .",". ucfirst($lang) .",". strtolower($lang);

    // ReactOS and Wine Strings - array
    $ignoredROSStrings = file($ROSSpellFilename, FILE_IGNORE_NEW_LINES);
    $ignoredWineStrings = file($wineSpellFilename, FILE_IGNORE_NEW_LINES);

    $regex->rewind();
    while($regex->valid())
    {
        if (!$regex->isDot())
        {
            $file = glob($regex->getPathInfo() ."/*{". $fileSearch ."}*.". $fileExt, GLOB_BRACE);

            $isFile = array_filter($file);

            if (empty($isFile))
            {
                echo '<b>No translation</b> for path '. $regex->getPathInfo() .'<hr>';
            } else
            {
                $fileContent1 = file_get_contents($regex->key());
                $fileContent2 = file_get_contents($file[0]);

                $array = diff_versions($fileContent1, $fileContent2);

                if ($array['diff'])
                {
                    echo $regex->getPathInfo() .'<br><br>';

                    $currentMissing = $missing;

                    foreach ($array['leftVersion'] as $index => $english)
                    {
                        // Catch offset error
                        try
                        {
                            // Check if this same and ignore some words
                            if ($english === $array['rightVersion'][$index] && !in_array($english, $ignoredROSStrings) && !in_array($english, $ignoredWineStrings))
                            {
                                echo "<b>Missing translation:</b> ". htmlspecialchars($english) ."<br>";
                                $missing++;
                            }
                            $allStrings++;
                        } catch (Exception $e)
                        {
                            echo "Missing stuff in your language<br>";
                            $allStrings++;
                            $missing++;
                        }
                    }
                    if ($currentMissing == $missing)
                    {
                        echo "Seems OK :) Some strings was ignored by ReactOS and Wine spell files.<br>";
                    }

                    echo "<hr>";
                }
            }
        }
        $regex->next();
    }
    echo "<h3>All strings for english: $allStrings</h3>";
    echo "<h3>Missing translations for your language ($lang): $missing</h3>";

    // Rounded percent
    $percent = round((($allStrings - $missing) / $allStrings) * 100, 2);
    echo "<h3>Language $lang translated in $percent%</h3>";
}

include_once('footer.php');
