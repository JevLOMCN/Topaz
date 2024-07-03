import { MapPointsObject } from '@/data/Maps'

export const navigationMaps: mapTypes[] = ['Global Map', 'Snake Pit Area']

export default function MapPoints({
  lastMap,
  onPointClick,
}: {
  lastMap: mapTypes
  onPointClick: (label: string) => void
}) {
  return (
    <>
      {MapPointsObject[lastMap]?.map(({ label, pos }, index) => (
        <label
          key={index}
          className="group absolute flex -translate-x-1/2 -translate-y-[calc(100%-1rem)] cursor-pointer flex-col items-center gap-2 p-2"
          style={{ left: `${pos[0]}%`, top: `${pos[1]}%` }}
        >
          <p className="text-lg font-medium text-white drop-shadow-lg transition-transform group-hover:-translate-y-2">
            {label}
          </p>
          <button
            onClick={() => onPointClick(label)}
            className="h-5 w-5 rounded-full border-2 border-primary-400 bg-primary-700/40 transition-transform group-hover:scale-150"
          ></button>
        </label>
      ))}
    </>
  )
}
