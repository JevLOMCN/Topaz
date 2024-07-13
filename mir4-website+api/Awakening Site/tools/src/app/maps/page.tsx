'use client'
import { MapsAtom, MapsFloorAtom } from '@/atoms/Maps'
import InteractiveMap from '@/components/maps/InteractiveMap'
import FloorsHandler from '@/components/maps/InteractiveMap/FloorsHandler'
import ManageMap from '@/components/maps/InteractiveMap/ManageMap'
import MapButton from '@/components/maps/MapButton'
import MapPoints, { navigationMaps } from '@/components/maps/MapPoints'
import { mapFloors } from '@/data/Maps'

import { cn } from '@/utils/classNames'
import { toCamelCase } from '@/utils/index'
import { useAtom } from 'jotai'
import Image from 'next/image'
import React from 'react'

export default function Maps() {
  const [mapsStack, setMapsStack] = useAtom(MapsAtom)
  const [mapsFloor, setMapFloor] = useAtom(MapsFloorAtom)

  const handleMapChange = (selected: string) => {
    const results = [...mapsStack]

    // remove maps until the selected
    for (let mapIndex = mapsStack.length - 1; mapIndex >= 0; mapIndex--) {
      if (results.at(mapIndex) !== selected) results.pop()
      else break
    }

    setMapFloor(0)
    setMapsStack(results)
  }

  const lastMap = mapsStack.at(-1) as mapTypes
  const isNavigationMap = navigationMaps.includes(lastMap ?? '')
  const currentMapFloors = mapFloors?.[toCamelCase(lastMap) as subMapsWithFloor]

  return (
    <div className="relative mx-auto flex w-full max-w-[90rem] justify-center gap-4 px-6 pt-40 selection:bg-primary-800 md:pl-[5.25rem] md:pt-32">
      <div
        className={cn(
          'relative flex min-h-[45rem] w-auto max-w-5xl flex-col gap-4 rounded-md border-2 border-white/10 p-4 backdrop-blur-md',
          { 'w-full': isNavigationMap }
        )}
      >
        <header className="relative mb-auto mr-auto flex flex-row items-center gap-4 text-xl font-bold text-white">
          {mapsStack.map((map, index) => (
            <React.Fragment key={index}>
              <MapButton onClick={() => handleMapChange(map)}>{map}</MapButton>
              {index < mapsStack.length - 1 ? <p>{'>'}</p> : <></>}
            </React.Fragment>
          ))}
        </header>

        {isNavigationMap ? (
          <Image
            src={`/maps/${toCamelCase(lastMap)}.webp`}
            alt=""
            fill
            className={'absolute -z-10 rounded-md object-cover'}
            sizes="100%"
          />
        ) : (
          <>
            {currentMapFloors ? (
              <FloorsHandler
                floors={currentMapFloors}
                activeIndex={mapsFloor}
                action={(floorIndex) => setMapFloor(floorIndex)}
              />
            ) : (
              <></>
            )}
            <InteractiveMap mapsStack={mapsStack} floor={mapsFloor} />
          </>
        )}

        {lastMap === 'Global Map' ? (
          <button
            onClick={() => {
              setMapsStack((prev) => [...prev, 'Secret Peak'])
              setMapFloor(0)
            }}
            className="group relative ml-auto flex h-[12rem] w-full items-end justify-end overflow-hidden rounded-md p-2 text-2xl font-bold text-white transition-[width,height] hover:h-[16rem] hover:w-[40rem] md:w-[35rem]"
          >
            <Image
              src={'/maps/secret_peak_miniature.webp'}
              alt=""
              fill
              className="absolute rounded-md object-cover"
            />

            <p className="absolute drop-shadow-[0_0_8px_rgba(0,0,0,0.8)] transition-transform group-hover:-translate-y-3">
              Secret Peak
            </p>
          </button>
        ) : (
          <></>
        )}

        <MapPoints
          lastMap={lastMap}
          onPointClick={(label) => {
            setMapsStack((prev) => [...prev, label])
            setMapFloor(0)
          }}
        />
      </div>

      {isNavigationMap ? (
        <></>
      ) : (
        <ManageMap mapFloor={currentMapFloors?.[mapsFloor]} />
      )}
    </div>
  )
}
