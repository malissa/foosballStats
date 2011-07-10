#!/usr/bin/perl -wT

USE strict;
USE CGI;
USE CGI::Carp qw ( fatalsToBrowser ); 

$CGI::POST_MAX = 1024 * 5000;

my $query = new CGI; 
my $file = $query->param("file");

print $cgi->header();
print $file;


