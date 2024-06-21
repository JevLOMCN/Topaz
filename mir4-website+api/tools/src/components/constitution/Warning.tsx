'use client'

import { statusLevelsAtom } from '@/atoms/Constitution'
import { useAtomValue } from 'jotai'
import { useEffect, useState } from 'react'
import { useTranslation } from '../../../public/locales/client'

export default function ConstitutionWarning() {
  const levels = useAtomValue(statusLevelsAtom)
  const [showError, setShowError] = useState(false)
  const { t } = useTranslation()

  useEffect(() => {
    setShowError(false)

    const minLevel = Math.min(
      ...Object.values(levels).map((values) => values.from)
    )
    const maxLevel = Math.max(
      ...Object.values(levels).map((values) => values.to)
    )

    if (maxLevel - minLevel > 4) setShowError(true)
  }, [JSON.stringify(levels)])

  return showError ? (
    <div className="max-w-lg rounded border-2 border-yellow-300 bg-yellow-300/10 p-3">
      <p className="text-center font-medium text-white">
        {t(
          'Attention, you have a status upgrade that requires a higher constitution tier and consequentially an upgrade in all other stats. The cost for them is not being accounted for.'
        )}
      </p>
    </div>
  ) : (
    <></>
  )
}
