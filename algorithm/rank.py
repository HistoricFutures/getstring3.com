#!/usr/bin/python

import getopt
import os
import sys
import time

from helpers import load_questions, load_entities, load_recipe, network_responsive_median_over_two, \
    rank, consolidate_history, build_history_table

from analysis import ranking_strip, population_ranks, average_population_rank

def main(argv):
    source = None
    try:
        opts, args = getopt.getopt(argv,"hi:",["ifile=",])
    except getopt.GetoptError:
        print 'rank.py -i <input source>'
        sys.exit(2)
    for opt, arg in opts:
        if opt == '-h':
            print 'rank.py -i <input source>'
            sys.exit()
        elif opt in ("-i", "--ifile"):
            source = arg

    if source is None:
        print 'rank.py -i <input source>'
        sys.exit(2)

    #
    # source should be a directory
    #


    if not os.path.isdir(source):
        print 'directory of input source expected'
        sys.exit(2)
    if source[-1] != '/':
        source += '/'

    start = time.time()

    network_function = network_responsive_median_over_two
    Q = load_questions(source)
    history = consolidate_history(Q, network_function)
    r = rank(history, pretty=False)
    end = time.time()

    print "- " * 80
    print "{count} entities, across {q} questions, ranked in {t} seconds".format(
        count=len(r),
        q=len(Q),
        t=end-start)
    print "- " * 80


    E = load_entities(source)
    recipe = load_recipe(source)

    #
    # Coloured strip of rankings
    #
    ranking_strip(r, E, recipe, output_path=source, filter="T2")
    pr = population_ranks(r, E)
    print average_population_rank(pr)
    sys.exit(0)


if __name__ == "__main__":
   main(sys.argv[1:])


if False:
    #
    # Execute
    #




    entity_question_lookup = {}
    for q in Q:
        for n in Q[q]['graph'].nodes():
            if n not in entity_question_lookup:
                entity_question_lookup[n] = []
            entity_question_lookup[n].append(q)

    for e in E:
        print e, entity_question_lookup.get(str(e))


    save_all_questions(Q, data_path)

    import xlsxwriter
    from xlsxwriter.utility import xl_col_to_name

    workbook = xlsxwriter.Workbook("{path}summary.xlsx".format(path=data_path))
    summary = workbook.add_worksheet("summary")
    # Add a bold format to use to highlight cells.
    bold = workbook.add_format({'bold': True})

    summary.write("A1", "Entities", bold)
    summary.write("B1", "Population", bold)
    summary.set_column(0, 0, 40)
    col_offset = 2
    col = col_offset
    for q in range(1, len(Q)+1):
        summary.write(1, col, q, bold)
        col += 1

    summary.set_column(0, 1, 40)
    row = 2

    history = build_history_table(Q, network_function, verbose=False)

    for e in E:
        h = history.get(str(e))
        if h is not None:

            name = "{name}".format(name=E[e]['name'])
            population = "{population}".format(population=E[e]['population'])
            summary.write(row, 0, name)
            summary.write(row, 1, population)
            for q, score in h:
                summary.write(row, col_offset -1 + q, score)
            row += 1

    workbook.close()


    # from ipdb import set_trace; set_trace()
    # print Q[16]



