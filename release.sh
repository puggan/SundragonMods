cd "$(dirname "$0")/src"
export SMVER="`grep puggan -B 1 manifest.json | grep version | sed -e 's@.*: "@@' -e 's@".*@@'`"
zip ../releases/SundragonMods-v${SMVER}.zip manifest.json
cd ..
git add releases/SundragonMods-v${SMVER}.zip
git commit -m "Release v${SMVER}" && git tag "v${SMVER}" && git push github master "v${SMVER}"
