"""Remove only the white outer canvas from the approved TS-09c favicon.

The white lettering INSIDE the green logo must stay unchanged.
Dependency-free; used by the website's existing GitHub build pipeline.
"""
import struct
import zlib
from collections import deque

SIGNATURE = b'\x89PNG\r\n\x1a\n'

def png_rgba(image):
    if not image.startswith(SIGNATURE):
        raise ValueError('Not a PNG')
    cursor = 8
    chunks = []
    while cursor < len(image):
        size = struct.unpack_from('>I', image, cursor)[0]
        tag = image[cursor+4:cursor+8]
        payload = image[cursor+8:cursor+8+size]
        chunks.append((tag, payload))
        cursor += 12 + size
        if tag == b'IEND':
            break
    header = next(data for tag, data in chunks if tag == b'IHDR')
    width, height, depth, color, compression, filtering, interlace = struct.unpack('>IIBBBBB', header)
    if (depth, color, compression, filtering, interlace) != (8, 6, 0, 0, 0):
        raise ValueError('Expected an 8-bit non-interlaced RGBA favicon')
    raw = zlib.decompress(b''.join(data for tag, data in chunks if tag == b'IDAT'))
    stride = width * 4
    pos = 0
    rows = []
    prev = bytearray(stride)
    for _ in range(height):
        filt = raw[pos]
        pos += 1
        row = bytearray(raw[pos:pos+stride])
        pos += stride
        if len(row) != stride or filt > 4:
            raise ValueError('Unsupported or truncated PNG scanline')
        for i in range(stride):
            a = row[i-4] if i >= 4 else 0
            b = prev[i]
            c = prev[i-4] if i >= 4 else 0
            if filt == 1:
                predictor = a
            elif filt == 2:
                predictor = b
            elif filt == 3:
                predictor = (a+b)//2
            elif filt == 4:
                p = a+b-c
                pa, pb, pc = abs(p-a), abs(p-b), abs(p-c)
                predictor = a if pa <= pb and pa <= pc else b if pb <= pc else c
            else:
                predictor = 0
            row[i] = (row[i]+predictor)&255
        rows.append(row)
        prev = row
    return width, height, rows

def make_png(width, height, rows):
    def chunk(tag, payload):
        return (struct.pack('>I', len(payload))+tag+payload
                +struct.pack('>I', zlib.crc32(tag+payload)&0xffffffff))
    raster = b''.join(b'\x00'+bytes(row) for row in rows)
    ihdr = struct.pack('>IIBBBBB', width, height, 8, 6, 0, 0, 0)
    return SIGNATURE+chunk(b'IHDR',ihdr)+chunk(b'IDAT',zlib.compress(raster,9))+chunk(b'IEND',b'')

def clean_favicon(image):
    width, height, rows = png_rgba(image)
    # White TS letters are surrounded by green, so only WHITE connected
    # to the outside of the image is removed.
    def outside(x,y):
        r,g,b,a = rows[y][4*x:4*x+4]
        return a == 0 or (min(r,g,b) >= 215 and max(r,g,b)-min(r,g,b) <= 24)
    seen = bytearray(width*height)
    q = deque([(x,0) for x in range(width)]
              +[(x,height-1) for x in range(width)]
              +[(0,y) for y in range(height)]
              +[(width-1,y) for y in range(height)])
    while q:
        x,y = q.popleft()
        index = y*width+x
        if seen[index] or not outside(x,y):
            continue
        seen[index] = 1
        rows[y][4*x+3] = 0
        if x: q.append((x-1,y))
        if x+1 < width: q.append((x+1,y))
        if y: q.append((x,y-1))
        if y+1 < height: q.append((x,y+1))
    opaque = [(x,y) for y in range(height) for x in range(width)
              if rows[y][4*x+3] > 0]
    if not opaque:
        raise ValueError('Entire favicon was removed')
    xmin = min(x for x,y in opaque)
    xmax = max(x for x,y in opaque)
    ymin = min(y for x,y in opaque)
    ymax = max(y for x,y in opaque)
    w,h = xmax-xmin+1, ymax-ymin+1
    side = max(w,h)+6
    dx,dy = (side-w)//2,(side-h)//2
    result = [bytearray(side*4) for _ in range(side)]
    for y in range(h):
        result[dy+y][4*dx:4*(dx+w)] = rows[ymin+y][4*xmin:4*(xmax+1)]
    return make_png(side,side,result)
