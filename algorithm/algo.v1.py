import operator
import sqlite3
import networkx as nx
import numpy
from numpy.core.fromnumeric import mean

data_path = '../web/datasets/2015-08-11T14:20:25+00:00/'

#
# Utils
#


def median(lst):
    return numpy.median(numpy.array(lst))


def save_question_graph(Q, n, data_path):
    import matplotlib.pyplot as plt
    plt.title('Question {} initiated by {}'.format(n, Q[n]['initiator']))
    G = Q[n]['graph']
    i = Q[n]['initiator']
    # nx.draw_graphviz(Q[n]['graph'], with_labels=True)
    engine = 'twopi'
    pos = nx.graphviz_layout(G, prog=engine, root=i)
    nx.draw(G, pos,
            with_labels=True,
            alpha=1,
            node_size=550)

    plt.savefig("{path}question-{n}.png".format(path=data_path, n=n))
    plt.clf()

    #
    # Cyclical graphs aren't rendered properly
    #
    #
    dot_file = "{path}question-{n}.dot".format(path=data_path, n=n)
    png_file = "{path}question-{n}-dotfile.png".format(path=data_path, n=n)

    nx.write_dot(G, dot_file)

    from fabric.api import env, run, settings, sudo, hosts, local
    local("dot -Tpng {} > {}".format(dot_file, png_file))


def save_all_questions(Q, data_path):
    for n in xrange(1, len(Q)+1):
        save_question_graph(Q, n, data_path)


def get_useful_cursor(connection, table):
    c = connection.cursor()
    c.execute('SELECT * FROM {}'.format(table))
    columns = [col[0] for col in c.description]
    for raw in c.fetchall():
        yield dict(zip(columns, raw))


def load_entities(data_path):
    conn = sqlite3.connect("{path}dataset.sqlite3".format(path=data_path))
    table = {}
    for row in get_useful_cursor(conn, 'entity'):
        table[row['id']] = row

    conn.close()
    return table


def load_questions(data_path):
    conn = sqlite3.connect("{path}dataset.sqlite3".format(path=data_path))

    questions = {}

    for row in get_useful_cursor(conn, 'ask'):
        question = row['question']

        if question not in questions:
            questions[question] = {'graph': nx.DiGraph(), 'initiator': []}

        start = row.get('time_start')
        end = row.get('time_ack')
        answer = row.get('answer')

        questions[question]['graph'].add_edge(str(row['entity_from']),
                                              str(row['entity_to']),
                                              start=start,
                                              end=end,
                                              answer=answer)
    conn.close()

    #
    # find the initiators
    #

    for q in questions:
        g = questions[q]['graph']
        for n in g.nodes_iter():
            if g.predecessors(n) == []:
                questions[q]['initiator'] = n
                break
        if questions[q]['initiator'] == []:
            #
            # Found a question that starts with a vertical
            #
            questions[q]['initiator'] = g.nodes()[0]

    return questions


#
# Collecting raw data
#

def build_ack_table(G, verbose=False):
    table = {}
    for u, v, edata in G.edges(data=True):
        start = edata.get('start')
        end = edata.get('end')
        if end is None:
            if verbose is True:
                print "{node} didn't acknowledge".format(node=v)
            continue
        if verbose is True:
            print "{node} acknowledged in {time} minutes".format(node=v, time=end-start)
        table[v] = {'ACK': end - start, 't': end}
    return table


def build_route_table(G, ack_table, verbose=False):
    table = {}
    for u, v, edata in G.edges(data=True):
        start = edata.get('start')
        end = edata.get('end')

        if u not in table:
            table[u] = []

        ack = ack_table.get(u)
        if ack is None:
            continue

        t = ack.get('t')

        table[u].append(start - t)

    return table


def build_raw_score_table(G, initiator, ack_table, route_table, verbose=False):
    #
    # ACK + max(ROUTE1, ROUTE2, ROUTE3)
    #
    table = {}

    for u, v, edata in G.edges(data=True):
        if u in table:
            continue

        a = ack_table.get(u)
        if a is None:
            ack = 0
        else:
            ack = a.get('ACK')
        route = route_table.get(u)
        if any(route):
            value = ack + max(route)
            table[u] = value
            if verbose is True:
                print "{node} = {ack} + max({route}) = {value}".format(
                    node=u, ack=ack, route=route, value=value)

    for node, ack in ack_table.items():
        if node in table:
            continue

        table[node] = ack.get('ACK')

    table[initiator] = 0
    return table


#
# Network Responsiveness
#

def network_responsive_median_over_two(g,
                                       initiator,
                                       node=None,
                                       ack_table=None,
                                       route_table=None,
                                       raw_score_table=None,
                                       table=None,
                                       verbose=False):

    if node is None:
        node = initiator

    if ack_table is None:
        ack_table = build_ack_table(g, verbose)
        if verbose is True:
            print "== ACK TABLE =="
            print ack_table

    if route_table is None:
        route_table = build_route_table(g, ack_table, verbose)
        if verbose is True:
            print "== ROUTING TABLE =="
            print route_table

    if raw_score_table is None:
        raw_score_table = build_raw_score_table(
            g, initiator, ack_table, route_table, verbose)
        if verbose is True:
            print "== RAW SCORE TABLE =="
            print raw_score_table

    if table is None:
        table = {}

    raw = raw_score_table.get(node)
    if raw is None:
        return 0, table

    child_scores = []
    successors = g.successors(node)
    if verbose is True:
        print "{node} has {n} successors {successors}".format(
            node=node, n=len(successors), successors=successors)
    if len(successors) > 0:

        for n in successors:
            score, _ = network_responsive_median_over_two(g=g,
                                                          initiator=initiator,
                                                          node=n,
                                                          ack_table=ack_table,
                                                          route_table=route_table,
                                                          raw_score_table=raw_score_table,
                                                          table=table,
                                                          verbose=verbose)

            child_scores.append(score)
        table[node] = raw + median(child_scores) / 2
        return table[node], table

    table[node] = raw
    return table[node], table


#
# Apply Environmental Coefficients
#

def apply_environmental_coefficients():
    pass


#
# Historic Consolidation
#

def build_history_table(questions, network_function, verbose=False):
    table = {}
    for uid, data in questions.items():
        initiator = data['initiator']

        _, network_values = network_function(g=data['graph'],
                                             initiator=initiator,
                                             verbose=verbose)

        for entity, value in network_values.items():
            if entity not in table:
                table[entity] = []

            table[entity].append(value)
    return table


def reduce_history(table):
    reduced = {}
    for uid, history in table.items():
        reduced[uid] = mean(history)
    return reduced


def consolidate_history(questions, network_function, verbose=True):
    return reduce_history(build_history_table(Q, network_function, verbose=verbose))


#
# Ranking
#

def rank(reduced_history_table, pretty=False):

    r = sorted(reduced_history_table.items(), key=operator.itemgetter(1))
    if pretty is False:
        return r

    for x in xrange(1, len(r)):
        print x, r[x]

#
# Execute
#

Q = load_questions(data_path)
save_all_questions(Q, data_path)

network_function = network_responsive_median_over_two
print rank(consolidate_history(Q, network_function), pretty=True)
