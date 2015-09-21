#!/usr/bin/python

import getopt
import os
import sys
import time
import requests
from bs4 import BeautifulSoup

def main(argv):
    count = 1
    source = None
    try:
        opts, args = getopt.getopt(argv,"hi:n:",["ifile=","ncount="])
    except getopt.GetoptError:
        print 'rank.py -i <recipe file> -n <count>'
        sys.exit(2)
    for opt, arg in opts:
        if opt == '-h':
            print 'rank.py -i <recipe file> -n <count>'
            sys.exit()
        elif opt in ("-i", "--ifile"):
            source = arg
        elif opt in ("-n", "--count"):
            count = int(arg)


    if source is None:
        print 'rank.py -i <recipe file> -n <count>'
        sys.exit(2)




    endpoint = "http://localhost:8080/data/new"


    client = requests.session()

    for counter in xrange(1, count + 1):
        start = time.time()
        r = client.get(endpoint)

        soup = BeautifulSoup(r.text, "html.parser")
        fileinput = soup.find('input', type="file")
        token = soup.find('input', type="hidden")

        files = {fileinput['name']: open(source, 'rb')}
        r = client.post(endpoint, data={token['name']: token['value']}, files=files)
        end = time.time()

        print "- " * 80
        print "data generation round {n} took {t} seconds".format(n=counter, t=end-start)
        print "- " * 80


    sys.exit(0)


if __name__ == "__main__":
   main(sys.argv[1:])

