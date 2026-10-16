"""Deterministic Pillow crop/matte only. No AI models, generated pixels or inpainting."""
from pathlib import Path
from PIL import Image, ImageOps, ImageDraw, ImageFilter

root=Path(__file__).resolve().parents[1]/'assets/images/editorial'
source=Image.open(root/'headphones-source.webp').convert('RGBA')
# Hand-traced contour of the photographed headphones; exclude desk, keyboard and shadow.
outer=[(377,366),(375,337),(386,287),(412,236),(456,190),(508,164),(568,154),(615,154),(666,164),(707,188),(746,225),(779,267),(800,310),(815,354),(817,393),(826,423),(831,471),(826,519),(813,564),(787,604),(750,629),(698,645),(661,642),(622,629),(594,617),(565,625),(534,639),(494,648),(452,640),(412,618),(385,586),(367,548),(357,503),(358,461),(367,420),(380,388)]
inner=[(406,355),(413,316),(438,277),(471,248),(517,230),(563,223),(611,224),(656,234),(695,256),(724,288),(747,329),(758,367),(729,364),(695,369),(660,388),(629,419),(606,464),(600,503),(588,496),(574,460),(554,422),(525,391),(492,370),(458,354),(425,353)]
mask=Image.new('L',source.size,0);draw=ImageDraw.Draw(mask)
draw.polygon(outer,fill=255);draw.polygon(inner,fill=0)
source.putalpha(mask.filter(ImageFilter.GaussianBlur(.8)))
source=source.crop(source.getbbox())
source.save(root/'headphones-cutout.webp',lossless=True)
# Crop the already downloaded photos to consistent card proportions.
for name in ['shopping','food','travel','technology','beauty','cinema','living','fashion']:
    im=Image.open(root/(name+'.webp')).convert('RGB')
    ImageOps.fit(im,(720,480),method=Image.Resampling.LANCZOS).save(root/(name+'-card.webp'),quality=82)
print('Prepared real-photo cutout and eight card crops.')
