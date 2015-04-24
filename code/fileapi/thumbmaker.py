import Image
import sys

if (len(sys.argv) < 5):
  print "sorry, not enough args"
  sys.exit()
else:
  im = Image.open(sys.argv[1])
  #print im.format, im.size, im.mode
  #print "making thumbnail..."
  imgheight = int(sys.argv[3])
  imgwidth = int(sys.argv[4])
  size = [imgheight, imgwidth]
  im.thumbnail(size, Image.ANTIALIAS)
  if im.mode != "RGB":
    im = im.convert("RGB")
  im.save(sys.argv[2], "JPEG")
  print "done"
