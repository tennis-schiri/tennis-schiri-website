#!/usr/bin/env python3
"""Deterministic reordering of the exact 136 slides and 25 videos in the 2026 workshop bundle."""
import copy, json, re, shutil
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

BLOCKS = [
 ("START", [1,2,3,4]),
 ("01 · GESCHICHTE", [5,6,7,107,8,9,10,11]),
 ("02 · TENNISPLATZ", [12,13,14,15,16]),
 ("03 · SCHLÄGER", [17,18,19,20,21,22]),
 ("04 · ZÄHLWEISE", [23,26,24,25,27,28,29,30,31]),
 ("05 · MATCH", [32,33,34,35,36,38,37,70,71,72]),
 ("06 · OHNE SCHIEDSRICHTER", [76,77,78,79,40,41,42,43,44,45,46,47,48,63,64,73,74,75]),
 ("07 · REGELFÄLLE", [39,49,50,51,52,53,54,108,109,55,56,110,111,112,
                       57,58,59,60,113,114,115,116,61,62,117,118,119,120,
                       65,66,67,68,69]),
 ("08 · TVBB", [80,81,82,83,84,85,95,96,97,98,99,86,87,91,88,89,90,
                92,93,94,100,101,102,103,104]),
 ("09 · REGELWERKE", [136]),
 ("10 · ABSCHLUSS", [132,133,134,135]),
 ("BONUS · ARCHIV", [105,106,121,122,123,124,125,126,127,128,129,130,131]),
]
order=[n for _,part in BLOCKS for n in part]
assert len(order)==136 and sorted(order)==list(range(1,137))
chapter={n:ch for ch,part in BLOCKS for n in part}
src=Path('/tmp/workshop-extracted')
out=Path('_workshop_upload/_private')
slides=json.loads((src/'slides.json').read_text(encoding='utf-8'))
orig=slides['slides']
assert len(orig)==136 and [s['no'] for s in orig]==list(range(1,137))
assert sum(bool(s.get('video')) for s in orig)==25
assert len(list((src/'media').glob('clip-*.mp4')))==25

def label(n):
    return ('REGELWORKSHOP' if n==1 else 'START · FAHRPLAN' if n==2
            else 'VORSTELLUNG' if n in (3,4) else chapter[n])

def recolor(image_path,output_path,n,source):
    im=Image.open(image_path).convert('RGB')
    if im.size!=(1265,712): raise RuntimeError(f'Unexpected image dimensions for {source}')
    d=ImageDraw.Draw(im)
    bg=im.getpixel((1258,705))
    dark=sum(bg)/3<100
    fg=(215,253,71) if dark else (21,50,34)
    d.rectangle((1152,668,1222,699),fill=bg)
    font=ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',18)
    number=f'{n:03d}'
    bounds=d.textbbox((0,0),number,font=font)
    d.text((1201-(bounds[2]-bounds[0]),670),number,font=font,fill=fg)
    new_label=label(source)
    if new_label!=orig[source-1]['chapter']:
        d.rounded_rectangle((955,40,1202,89),radius=12,
            fill=(42,76,51) if dark else (224,234,212),
            outline=(77,104,76) if dark else (198,216,188),width=1)
        font2=ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',14 if len(new_label)<19 else 12)
        text=new_label.upper()
        bbox=d.textbbox((0,0),text,font=font2)
        d.text((1079-(bbox[2]-bbox[0])/2,57),text,font=font2,
            fill=(215,253,71) if dark else (58,95,66))
    # Chapter cover slides also print the chapter number in the body.
    if source in (39,76,106,136) and new_label!=orig[source-1]['chapter']:
        d.rectangle((67,126,835,178),fill=im.getpixel((11,151)))
        font3=ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',24)
        d.text((72,128),new_label.upper(),font=font3,fill=(215,253,71) if dark else (52,105,69))
    output_path.parent.mkdir(parents=True,exist_ok=True)
    im.save(output_path,'WEBP',quality=91,method=5)

new=[]
for index,old_no in enumerate(order,1):
    s=copy.deepcopy(orig[old_no-1])
    s['no']=index
    s['original_no']=old_no
    s['chapter']=label(old_no)
    if old_no in (105,106,*range(121,132)):
        s['optional']=True
    if old_no==135:
        s['main_end']=True
    if old_no==39:
        s['title']='REGELFÄLLE: WAS WÜRDEST DU ENTSCHEIDEN?'
    new.append(s)
    recolor(src/'slides'/f'slide-{old_no:03d}.webp',
            out/'slides'/f'slide-{index:03d}.webp',index,old_no)
slides['slides']=new
slides['version']=3
slides['date']='2026-11-18'
slides['main_end']=order.index(135)+1
assert slides['main_end']==123
assert sum(bool(s.get('video')) for s in new)==25
for x,y in zip(new,new[1:]):
    if x['type']=='question':
        assert y['type']=='answer' and x['title']==y['title'],f'Question/answer mismatch: {x["no"]}'
(out/'slides.json').write_text(json.dumps(slides,ensure_ascii=False,separators=(',',':')),encoding='utf-8')
(out/'original-to-current.json').write_text(json.dumps({str(n):i for i,n in enumerate(order,1)},indent=2),encoding='utf-8')
shutil.copytree(src/'media',out/'media',dirs_exist_ok=True)
deck=Path('workshop-site/index.html').read_text(encoding='utf-8')
payload=json.dumps(slides,ensure_ascii=False,separators=(',',':')).replace('<',r'\u003c')
deck,count=re.subn(r'(<script id="workshop-data" type="application/json">)([\s\S]*?)(</script>)',
    lambda m:m.group(1)+payload+m.group(3),deck,count=1)
assert count==1
logout='<a href="/logout.php" aria-label="Workshop verlassen" style="display:inline-flex;align-items:center;padding:8px 13px;border:1px solid #54755f;border-radius:9px;text-decoration:none;color:#d2fc46;font-weight:800;white-space:nowrap">Abmelden ↗</a>'
assert deck.count('</nav>')==1
(out/'index.html').write_text(deck.replace('</nav>',logout+'</nav>',1),encoding='utf-8')
print('136 Folien in 12 didaktischen Blöcken neu geordnet, 25 Clips erhalten, Hauptvortrag endet mit Folie 123.')
