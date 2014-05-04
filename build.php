<?php

if (isset($argv[1]) === false) {
    echo "Usage $argv[0] [version]\n";

    return;
}
$version = trim($argv[1], 'v');

echo "Updating git repository...\n";
shell_exec('git pull');

$tags = explode("\n", trim(shell_exec('git tag')));
if (in_array("v$version", $tags)) {
    echo "Version $version already exists\n";

    return;
}

$accessTokenCmd = 'composer config --global "github-oauth.github.com"';
$accessToken = shell_exec($accessTokenCmd);

$releaseCmd = strtr('curl --silent --data \'{"tag_name": "v%version%","target_commitish": "master","name": "v%version%","body": "Release %version% (%date%)","draft": false,"prerelease": false}\' https://api.github.com/repos/%owner%/%repository%/releases?access_token=%access_token%', array(
    '%date%' => date('Y-m-d'),
    '%version%' => $version,
    '%access_token%' => $accessToken,
    '%repository%' => 'subway',
    '%owner%' => 'eymengunay'
));
echo "Creating new release...\n";
shell_exec($releaseCmd);

echo "Updating git repository...\n";
shell_exec('git pull');

echo "Building phar archive...\n";
shell_exec('box build');

echo "Changing branch to gh-pages...\n";
shell_exec('git checkout gh-pages');

echo "Creating manifest json...\n";
if (file_exists('manifest.json') === false) {
    file_put_contents('manifest.json', json_encode(array(), JSON_PRETTY_PRINT));
}
$manifest = json_decode(file_get_contents('manifest.json'), true);
$manifest[] = array(
    'name'    => 'subway.phar',
    'sha1'    => sha1_file('subway.phar'),
    'url'     => "http://eymengunay.github.io/subway/downloads/subway-$version.phar",
    'version' => $version,
);
file_put_contents('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

echo "Copying phar archive...\n";
if (is_dir('downloads') === false) {
    mkdir('downloads');
}
copy('subway.phar', "downloads/subway-$version.phar");
copy('subway.phar', "downloads/subway.phar");

echo "Committing changes...\n";
shell_exec("git add downloads/subway-$version.phar");
shell_exec("git add downloads/subway.phar");
shell_exec("git add manifest.json");
shell_exec("git commit -m \"Bump version $version\"");

echo "Pushing gh-pages branch...\n";
shell_exec("git push origin gh-pages");

echo "Changing branch back to master...\n";
shell_exec('git checkout master');