#!/usr/bin/perl
# Description: Catalogues ".ttf" files and creates a MapServer fontset file
# Usage: perl make_fontset.pl <path>
#        -Where <path> is a folder or directory name, without an ending slash
#        -The path may need to be specific using slashes and not backslashes depending on your platform
# Output: will create or OVERWRITE a file called "fontset.txt" and includes full path to the ttf files.
# Example:  perl make_fontset.pl /usr/share/msfonts
# Date: October 4, 2002
# Original By: Tyler Mitchell - tmitchell at Lignum dot com

@dirs = @ARGV; #Must be full path - without a trailing slash
foreach $dir(@dirs){
    print "READING $dir...\n\n";
    opendir(DIR,$dir) || warn "Could not open $dir!";
    @dir = readdir(DIR);
    closedir(DIR);
#    $dir =~ s{\\}{/};  #Search and replace - make perl friendly single slashes
#    $dir =~ s{//}{/};  #Search and replace - make perl friendly single slashes
    foreach $file(@dir){
#	$file =~ s{\\}{/};  #Search and replace - make perl friendly single slashes
#	$file =~ s{//}{/};  #Search and replace - make perl friendly single slashes
        unless($file eq "." || $file eq ".."){
            if(-e "$dir/$file"){
		$results = "$file";
		$ttfcheck = uc(substr($file,-4,4));
		if ( length($results) > 0 and $ttfcheck eq '.TTF') {
			$results =~ s{.ttf}{};
			$results =~ s{.TTF}{};
			$results = lc($results);
			$logentry = "$results $dir/$file";
			$logentry =~ s{chr(47)}{/};
			`echo $logentry >> fontset.txt`;
		};
            }else{
                print "Error\: No such file -- $dir\\$file!\n";
            }
        }
    }
    undef @dir;
}


