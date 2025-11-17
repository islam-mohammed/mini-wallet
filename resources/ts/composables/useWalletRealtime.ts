import type { TransactionCreatedPayload } from '@/lib/wallet-realtime'

declare global {
  interface Window {
    Echo: any
  }
}

export function useWalletRealtime(
  userId: number,
  onTransaction: (payload: TransactionCreatedPayload) => void,
) {
  if (!window.Echo) {
    console.warn('Echo is not initialized')
    return
  }

  const channelName = `wallet.user.${userId}`

  window.Echo
    .private(channelName)
    .listen('.wallet.transaction.created', (payload: TransactionCreatedPayload) => {
      console.log('Realtime event received on channel', channelName, payload)
      onTransaction(payload)
    })
}
