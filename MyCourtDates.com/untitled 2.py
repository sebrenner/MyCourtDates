#!/usr/bin/env python
# encoding: utf-8
"""
courtDbClass.py
 
Defines a class and methods for updating the db.
 
Created by Scott Brenner on 2011-08-31.
Copyright (c) 2011 Scott Brenner. All rights reserved.
"""

import sys, os, unittest, MySQLdb


def getDbFreshness( ):
    connection = MySQLdb.connect(host='localhost', 
                        port=3306, user='todayspo_calAdm', 
                        passwd='Gmu1vltrkLOX4n', db='todayspo_courtCal2')
    curs = connection.cursor()
    try:
        queryString = "SELECT MAX(freshness) as freshness FROM nextActions;"
        curs.execute( queryString )
        freshness = curs.fetchall()
        freshness = freshness[0][0]
    except Exception, e:
        print "I couldn't get the max freshness."
        raise e
    return freshness.strftime( "%Y-%m-%d" )

getDbFreshness()