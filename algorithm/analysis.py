from PIL import Image
from PIL import ImageDraw
import hashlib


def average_population_rank(pop_ranks):
    apr = {}
    for pop, ranks in pop_ranks.items():
        apr[pop] = sum(ranks) / len(ranks)

    return apr


def population_ranks(rank, entities):
    pop_rank = {}

    rank_position = 0
    for r in rank:
        e = int(r[0])
        label = entities[e]['population']
        if label not in pop_rank:
            pop_rank[label] = []

        pop_rank[label].append(rank_position)
        rank_position += 1

    return pop_rank


def ranking_strip(rank, entities, recipe, output_path, minwidth=600, filter=""):

    colours = {}
    for population in recipe.get('populations'):
        l = population.get('label')
        c = population.get('colour')
        if l is not None and c is not None:
            colours[l] = [int(x) for x in c.strip('(').strip(')').split(',')]

    f = open("{}ranking_strip.png".format(output_path), "w+")

    filtered_rank = []
    for r in rank:
        e = int(r[0])
        label = entities[e]['population']
        if filter  in label:
            filtered_rank.append(r)

    bg_colour = (255, 255, 255)
    PALETTE = BLUE_PALETTE
    W = max(minwidth, len(filtered_rank))
    H = 50
    linewidth = int(float(W) / float(len(filtered_rank)))
    blank_canvas = Image.new("RGBA", (W, H), bg_colour)

    draw = ImageDraw.Draw(blank_canvas)
    xpos = 0
    for r in filtered_rank:
        e = int(r[0])
        label = entities[e]['population']

        if label in colours:
            colour = tuple(colours.get(label))
        else:
            m = hashlib.md5()
            m.update(label)
            m.hexdigest()
            colour = PALETTE[int(m.hexdigest(), 16) % len(PALETTE)]
        draw.line((xpos, 0) + (xpos, H), width=linewidth, fill=colour)
        xpos += linewidth


    blank_canvas.save(f, "PNG")

#
# http://www.flatuicolorpicker.com/blue
#
BLUE_PALETTE = [(103, 128, 159),
                (107, 185, 240),
                (129, 207, 224),
                (137, 196, 244),
                (197, 239, 247),
                (228, 241, 254),
                (25, 181, 254),
                (30, 139, 195),
                (31, 58, 147),
                (34, 167, 240),
                (34, 49, 63),
                (37, 116, 169),
                (44, 62, 80),
                (51, 110, 123),
                (52, 152, 219),
                (52, 73, 94),
                (58, 83, 155),
                (65, 131, 215),
                (68, 108, 179),
                (75, 119, 190),
                (82, 179, 217),
                (89, 171, 227),
                (92, 151, 191),
                ]