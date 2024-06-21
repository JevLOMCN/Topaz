import { SettingsAtom } from '@/atoms/Settings'
import i18next from 'i18next'
import LanguageDetector from 'i18next-browser-languagedetector'
import { useAtomValue } from 'jotai'
import { useEffect } from 'react'
import {
  initReactI18next,
  useTranslation as useTranslationOrg,
  type UseTranslationOptions,
} from 'react-i18next'
import ENUS_CONQUEST from './en/conquest.json'
import ENUS from './en/en-us.json'
import ES_CONQUEST from './es/conquest.json'
import ES from './es/es.json'
import PTBR_CONQUEST from './pt/conquest.json'
import PTBR from './pt/pt-br.json'
import { languages } from './settings'

const resources = {
  pt: {
    translation: { ...PTBR },
    conquest: PTBR_CONQUEST,
  },
  en: {
    translation: { ...ENUS },
    conquest: ENUS_CONQUEST,
  },
  es: {
    translation: { ...ES },
    conquest: ES_CONQUEST,
  },
}

const runsOnServerSide = typeof window === 'undefined'

void i18next
  .use(initReactI18next)
  .use(LanguageDetector)
  .init({
    fallbackLng: 'en',
    debug: false,
    resources,
    interpolation: {
      escapeValue: false,
    },
    lng: 'en',
    ns: '',
    detection: {
      order: ['path', 'htmlTag', 'cookie', 'navigator'],
    },
    preload: runsOnServerSide ? languages : [],
  })

export default i18next

export function useTranslation(
  ns?: string,
  options?: UseTranslationOptions<undefined> | undefined
) {
  const settings = useAtomValue(SettingsAtom)
  const language = settings.language ?? 'en'
  const ret = useTranslationOrg(ns, options)

  const { i18n } = ret

  if (runsOnServerSide && i18n.resolvedLanguage !== language) {
    useEffect(() => {
      i18n.changeLanguage(language).catch((err) => {
        console.error(err)
      })
    }, [])
  } else {
    useEffect(() => {
      if (i18n.resolvedLanguage === language) return
      i18n.changeLanguage(language).catch((err) => {
        console.error(err)
      })
    }, [language, i18n])
  }
  return ret
}
