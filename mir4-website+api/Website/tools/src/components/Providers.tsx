'use client'

import { Provider } from 'jotai'
import { I18nextProvider } from 'react-i18next'
import i18n from '../../public/locales/client'

function Providers({ children }: { children: React.ReactNode }) {
  return (
    <I18nextProvider i18n={i18n} defaultNS={'translation'}>
      <Provider>{children}</Provider>
    </I18nextProvider>
  )
}

export default Providers
