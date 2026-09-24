#!/bin/env python3

from time import time
from uuid import uuid4
from xmltodict import parse
import sqlite3

listids = {}
dbdata = []

fp = open("toodledo.xml")
xmldata = parse(fp.read())

dbcon = sqlite3.connect("todolist.db")

# Create a Context to list_id table
dbcur = dbcon.cursor()
result = dbcur.execute("SELECT id,name from lists")
for row in result.fetchall():
    listids.update({row[1]:row[0]})
dbcur.close()

for nodes in xmldata['xml']['item']:
    title = nodes['title']
    title.replace("'","''")

    # Encase all notes inside code fences
    if nodes['note'] == None:
        note = ''
    else:
        note = '```\n'+nodes['note']+'\n```'
    note = note.replace("'","''")

    try:
        listid = listids[nodes['context']]
    except:
        print(f"Fail no List ID for: {nodes['context']}")
        exit(1)

    ow = 1
    dbdata.append((
        str(uuid4()),
        listid,
        int(time()),
        int(time()),
        title,
        note,
        ow
    ))

dbcur = dbcon.cursor()
dbcur.executemany(
    '''INSERT INTO todolist (
        uuid,
        list_id,
        d_created,
        d_edited,
        title,
        note,
        ow
        ) VALUES(?,?,?,?,?,?,?)'''
        ,dbdata
)

dbcur.close()
dbcon.commit()
dbcon.close()

print(f"Loaded {len(dbdata)} Records")
