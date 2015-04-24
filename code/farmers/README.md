# Median Transcode Farmer Node

This requires Node.js, and no special dependencies other than having `handbrake-cli` installed.

## Configuration

Before use, make sure to edit `median-pxe-farmer.js` and configure the variables near the top of the file to match your environment.

The "remote working area" refers to an NFS share that the farmer can use as a "scratch disk" if it doesn't have enough space on the local storage. It counts RAM as local storage because it's meant to be loaded via PXE/netboot, using RAM as its disk.

The farmer depends on having read/write access to the file storage of the entire Median cluster.

## See Also

The farmer/farm part of Median was open sourced previously, [here](https://github.com/cyle/transcode-farm). You can refer to that to get a broader sense of how it could work, though the code included here is more up-to-date.